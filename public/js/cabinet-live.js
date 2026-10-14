/*
 * Автообновление кабинета продавца: статус товара и колокольчик меняются сами, без перезагрузки страницы.
 *
 * Как это работает:
 *  1. Страница раз в несколько секунд спрашивает у сервера «что изменилось» (GET account/live). Ответ крошечный:
 *     подпись состояния товара, метка уведомлений и через сколько секунд спросить снова.
 *  2. Если подпись или метка не совпали с теми, с которыми страница открыта, скрипт один раз загружает свежий вид этой
 *     же страницы и подменяет только помеченные блоки: data-live-region="группа:имя" (группы: product, notifications).
 *     Поля формы, прокрутка и всё остальное остаются как есть.
 *  3. Опрос приостанавливается, пока вкладка скрыта, и сразу выполняется, когда вкладку открыли снова.
 *  4. Если пять минут нет действий пользователя (мышь, клавиатура, прокрутка, касание), опрос останавливается:
 *     забытая открытой вкладка не нагружает сервер. При первом же действии страница сразу проверяет состояние.
 *
 * Разметка страницы товара:  data-live-product="ID"  data-live-signature="подпись"  [data-live-reload]
 *   data-live-reload: если подменить блоки не удалось, страницу можно просто перезагрузить (на форме нельзя: пропадёт ввод).
 * Разметка колокольчика:     data-live-notifications="число:номер последнего"
 * Атрибуты элемента, которые надо перенести со свежей страницы: data-live-sync="имя-атрибута ..." (элемент с id).
 */
(function () {
    'use strict';

    var script = document.currentScript;
    var endpoint = script && script.getAttribute('data-live-url');

    if (!endpoint || !window.fetch || !window.DOMParser || !document.querySelector) {
        return;
    }

    var DEFAULT_DELAY = 60;
    var MAX_DELAY = 300;
    var IDLE_LIMIT = 5 * 60 * 1000;

    var page = document.querySelector('[data-live-product]');
    var productId = page ? page.getAttribute('data-live-product') : '';
    var signature = page ? page.getAttribute('data-live-signature') : '';
    var bell = document.querySelector('[data-live-notifications]');
    var notifications = bell ? bell.getAttribute('data-live-notifications') : null;

    if (!productId && notifications === null) {
        return;
    }

    var timer = null;
    var busy = false;
    var failures = 0;
    var stopped = false;
    var lastActivity = Date.now();
    var idle = false;

    /** Давно ли пользователь что-то делал на странице. */
    function isIdle() {
        return Date.now() - lastActivity > IDLE_LIMIT;
    }

    function schedule(seconds) {
        window.clearTimeout(timer);

        if (stopped || document.hidden) {
            return;
        }

        if (isIdle()) {
            idle = true;

            return;
        }

        timer = window.setTimeout(check, seconds * 1000);
    }

    function check() {
        if (busy || stopped) {
            return;
        }

        // Таймер мог сработать уже после того, как пользователь «ушёл»
        if (isIdle()) {
            idle = true;

            return;
        }

        busy = true;

        fetch(endpoint + (productId ? '?product=' + encodeURIComponent(productId) : ''), {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (response.status === 401 || response.status === 403 || response.status === 419) {
                    // Вышли из аккаунта или сессия истекла: молча перестаём спрашивать
                    stopped = true;
                    throw new Error('stopped');
                }

                if (!response.ok) {
                    throw new Error('http ' + response.status);
                }

                return response.json();
            })
            .then(function (data) {
                failures = 0;

                return apply(data).then(function () {
                    return Math.max(5, parseInt(data.poll, 10) || DEFAULT_DELAY);
                });
            })
            .catch(function () {
                failures += 1;

                return Math.min(MAX_DELAY, DEFAULT_DELAY * failures);
            })
            .then(function (delay) {
                busy = false;
                schedule(delay);
            });
    }

    /** Сравнивает ответ сервера с тем, что показано на странице; при отличии подтягивает свежие блоки. */
    function apply(data) {
        var groups = [];

        if (productId) {
            if (!data.product) {
                // Товар удалили или он больше не наш: следить не за чем
                productId = '';
            } else if (data.product.signature !== signature) {
                groups.push('product');
            }
        }

        if (notifications !== null && data.notifications !== notifications) {
            groups.push('notifications');
        }

        return groups.length ? refresh(groups) : Promise.resolve();
    }

    function refresh(groups) {
        return fetch(window.location.href, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'text/html' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('http ' + response.status);
                }

                return response.text();
            })
            .then(function (html) {
                var fresh = new DOMParser().parseFromString(html, 'text/html');
                var complete = true;

                groups.forEach(function (group) {
                    complete = swap(group, fresh) && complete;
                });

                syncAttributes(fresh);
                remember(fresh, groups);

                if (!complete) {
                    fallback();
                }
            })
            .catch(function () {
                // Не получилось подтянуть страницу: оставляем как есть, в следующий раз попробуем снова
            });
    }

    /** Заменяет блоки группы свежими. Возвращает false, если на странице и в свежем виде набор блоков разошёлся. */
    function swap(group, fresh) {
        var prefix = group + ':';
        var complete = true;

        toArray(document.querySelectorAll('[data-live-region^="' + prefix + '"]')).forEach(function (current) {
            var name = current.getAttribute('data-live-region');
            var replacement = fresh.querySelector('[data-live-region="' + name + '"]');

            if (!replacement) {
                complete = false;

                return;
            }

            var node = document.importNode(replacement, true);

            current.parentNode.replaceChild(node, current);
            runScripts(node);
            flash(node);
        });

        toArray(fresh.querySelectorAll('[data-live-region^="' + prefix + '"]')).forEach(function (replacement) {
            if (!document.querySelector('[data-live-region="' + replacement.getAttribute('data-live-region') + '"]')) {
                complete = false;
            }
        });

        return complete;
    }

    /** Скрипты внутри подменённого блока сами не запускаются: создаём их заново. */
    function runScripts(root) {
        toArray(root.querySelectorAll('script')).forEach(function (old) {
            var copy = document.createElement('script');

            toArray(old.attributes).forEach(function (attribute) {
                copy.setAttribute(attribute.name, attribute.value);
            });
            copy.text = old.textContent;
            old.parentNode.replaceChild(copy, old);
        });
    }

    /** Короткая подсветка, чтобы продавец заметил, что статус изменился. */
    function flash(node) {
        node.classList.remove('live-updated');
        void node.offsetWidth;
        node.classList.add('live-updated');
    }

    /** Переносит атрибуты (например, режим формы редактирования) со свежей страницы на существующие элементы. */
    function syncAttributes(fresh) {
        toArray(document.querySelectorAll('[data-live-sync]')).forEach(function (element) {
            var source = element.id ? fresh.getElementById(element.id) : null;

            if (!source) {
                return;
            }

            element.getAttribute('data-live-sync').split(/\s+/).forEach(function (name) {
                if (!name) {
                    return;
                }

                if (source.hasAttribute(name)) {
                    element.setAttribute(name, source.getAttribute(name));
                } else {
                    element.removeAttribute(name);
                }
            });
        });
    }

    /** Запоминает состояние, которое теперь показано, чтобы не обновляться повторно впустую. */
    function remember(fresh, groups) {
        var freshPage = fresh.querySelector('[data-live-product]');
        var freshBell = fresh.querySelector('[data-live-notifications]');

        if (groups.indexOf('product') !== -1 && page && freshPage) {
            signature = freshPage.getAttribute('data-live-signature');
            page.setAttribute('data-live-signature', signature);
        }

        if (groups.indexOf('notifications') !== -1 && freshBell) {
            notifications = freshBell.getAttribute('data-live-notifications');

            if (bell) {
                bell.setAttribute('data-live-notifications', notifications);
            }

            var toggle = document.querySelector('.nav-notification .nav-item-toggle');

            if (toggle) {
                toggle.classList.toggle('icon-active', parseInt(notifications, 10) > 0);
            }
        }
    }

    /** Блоки подменить не удалось: на странице без формы просто обновляем её, на форме не трогаем ввод. */
    function fallback() {
        if (page && page.hasAttribute('data-live-reload')) {
            window.location.reload();
        }
    }

    function toArray(list) {
        return Array.prototype.slice.call(list);
    }

    /** Любое действие пользователя: запоминаем время, а если опрос стоял из-за простоя, сразу проверяем состояние. */
    function activity() {
        lastActivity = Date.now();

        if (idle) {
            idle = false;
            window.clearTimeout(timer);
            check();
        }
    }

    ['mousemove', 'mousedown', 'keydown', 'wheel', 'touchstart', 'scroll'].forEach(function (name) {
        window.addEventListener(name, activity, { passive: true, capture: true });
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            lastActivity = Date.now();
            idle = false;
            window.clearTimeout(timer);
            check();
        }
    });

    window.addEventListener('focus', function () {
        lastActivity = Date.now();
        idle = false;

        if (!busy) {
            window.clearTimeout(timer);
            check();
        }
    });

    // Первая проверка сразу: за время загрузки страницы статус мог измениться
    schedule(2);
})();
