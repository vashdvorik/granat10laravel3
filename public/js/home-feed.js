/*
 * Главная: быстрые фильтры ленты «Новинки» и блок для вернувшихся посетителей («Вы смотрели», напоминание об избранном).
 * Подключён на всех страницах витрины; каждая часть включается, только если на странице есть нужная разметка.
 * Всё хранится в памяти самого браузера (localStorage), на сервер личных данных не отправляется:
 *  - bimm_recent  — номера последних просмотренных товаров (страница товара помечена data-recent-product);
 *  - favorites    — избранное (его ведёт основной скрипт сайта).
 */
(function () {
    'use strict';

    var RECENT_KEY = 'bimm_recent';
    var RECENT_MAX = 12;
    var SHOWN_MAX = 8;

    function readIds(key) {
        try {
            var parsed = JSON.parse(window.localStorage.getItem(key) || '[]');

            if (!Array.isArray(parsed)) {
                return [];
            }

            return parsed
                .map(function (id) { return parseInt(id, 10); })
                .filter(function (id, index, all) { return Number.isInteger(id) && id > 0 && all.indexOf(id) === index; });
        } catch (e) {
            return [];
        }
    }

    function saveIds(key, ids) {
        try {
            window.localStorage.setItem(key, JSON.stringify(ids));
        } catch (e) {
            // приватный режим или запрет хранилища: просто не запоминаем
        }
    }

    function refreshWishlistHearts() {
        if (typeof window.initWishlists === 'function') {
            window.initWishlists();
        }
    }

    function plural(count, one, few, many) {
        var n = Math.abs(count) % 100;
        var last = n % 10;

        if (n > 10 && n < 20) { return many; }
        if (last === 1) { return one; }
        if (last >= 2 && last <= 4) { return few; }

        return many;
    }

    // 1. Запоминаем просмотренный товар
    var current = document.querySelector('[data-recent-product]');

    if (current) {
        var currentId = parseInt(current.getAttribute('data-recent-product'), 10);

        if (currentId > 0) {
            var recent = readIds(RECENT_KEY).filter(function (id) { return id !== currentId; });
            recent.unshift(currentId);
            saveIds(RECENT_KEY, recent.slice(0, RECENT_MAX));
        }
    }

    // 2. Быстрые фильтры над лентой «Новинки»: подгружают карточки нужной категории без перезагрузки страницы
    var chips = document.querySelector('.home-chips');
    var feed = document.getElementById('home-latest-feed');

    if (chips && feed && window.fetch) {
        var feedUrl = chips.getAttribute('data-feed-url');
        var busy = false;

        chips.addEventListener('click', function (event) {
            var chip = event.target.closest ? event.target.closest('.home-chip') : null;

            if (!chip || busy || chip.classList.contains('is-active')) {
                return;
            }

            var previous = chips.querySelector('.home-chip.is-active');
            var category = chip.getAttribute('data-category') || '';

            busy = true;
            chips.querySelectorAll('.home-chip').forEach(function (item) { item.classList.toggle('is-active', item === chip); });
            feed.classList.add('home-feed--loading');

            fetch(feedUrl + (category ? '?category=' + encodeURIComponent(category) : ''), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('http ' + response.status);
                    }

                    return response.text();
                })
                .then(function (html) {
                    feed.innerHTML = html;
                    feed.scrollLeft = 0;
                    refreshWishlistHearts();

                    if (chip.scrollIntoView) {
                        chip.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
                    }
                })
                .catch(function () {
                    // не получилось: оставляем прежнюю ленту и прежний выбранный фильтр
                    chips.querySelectorAll('.home-chip').forEach(function (item) { item.classList.toggle('is-active', item === previous); });
                })
                .then(function () {
                    busy = false;
                    feed.classList.remove('home-feed--loading');
                });
        });
    }

    // 3. Для вернувшихся: «Вы смотрели» и напоминание об избранном (у новичка блока нет вообще)
    var returns = document.getElementById('home-returns');

    if (returns && window.fetch) {
        var productsUrl = returns.getAttribute('data-products-url');
        var viewedBox = returns.querySelector('.home-returns__viewed');
        var viewedList = returns.querySelector('.home-returns__list');
        var favoritesLink = returns.querySelector('.home-returns__favorites');
        var favoritesText = returns.querySelector('.home-returns__favorites-text');

        var request = function (ids) {
            return fetch(productsUrl + '?ids=' + ids.join(','), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('http ' + response.status);
                }

                return response.json();
            });
        };

        var favoriteIds = readIds('favorites');
        var viewedIds = readIds(RECENT_KEY).slice(0, SHOWN_MAX);

        if (favoriteIds.length) {
            request(favoriteIds).then(function (data) {
                var total = parseInt(data.total, 10) || 0;

                if (total > 0) {
                    favoritesText.textContent = 'В избранном ' + total + ' ' + plural(total, 'товар', 'товара', 'товаров');
                    favoritesLink.hidden = false;
                    returns.hidden = false;
                }
            }).catch(function () {});
        }

        if (viewedIds.length) {
            request(viewedIds).then(function (data) {
                var holder = document.createElement('div');
                holder.innerHTML = data.html || '';

                // порядок «сначала недавние»: сервер отдаёт карточки в своём порядке
                var cardId = function (card) {
                    var marked = card.querySelector('[data-product-id]');

                    return marked ? parseInt(marked.getAttribute('data-product-id'), 10) : 0;
                };

                var cards = Array.prototype.slice.call(holder.querySelectorAll('.home-product')).sort(function (a, b) {
                    return viewedIds.indexOf(cardId(a)) - viewedIds.indexOf(cardId(b));
                });

                if (!cards.length) {
                    return;
                }

                cards.forEach(function (card) { viewedList.appendChild(card); });
                viewedBox.hidden = false;
                returns.hidden = false;
                refreshWishlistHearts();
            }).catch(function () {});
        }
    }
})();
