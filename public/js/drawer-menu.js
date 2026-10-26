/*
 * Боковое меню телефона: полное дерево категорий подгружается при первом касании кнопки меню.
 * До этого в списке лежат категории верхнего уровня (обычные ссылки), поэтому меню работает и без скрипта и при ошибке сети.
 * Раскрытие подкатегорий делает public/js/main.js (делегирование на .js-menubar).
 */
(function () {
    'use strict';

    var list = document.querySelector('.js-menubar[data-menu-url]');
    var button = document.querySelector('.js-push-menu');

    if (!list || !button || !window.fetch) {
        return;
    }

    var loading = false;

    function load() {
        var url = list.getAttribute('data-menu-url');

        if (loading || !url) {
            return;
        }

        loading = true;

        fetch(url, {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('menu ' + response.status);
                }

                return response.text();
            })
            .then(function (html) {
                list.innerHTML = html;
                list.removeAttribute('data-menu-url');
            })
            .catch(function () {
                // остаются категории верхнего уровня; следующее касание попробует ещё раз
                loading = false;
            });
    }

    // касание или наведение раньше клика: к моменту раскрытия меню дерево чаще всего уже готово
    ['touchstart', 'mouseenter', 'focus', 'click'].forEach(function (name) {
        button.addEventListener(name, load, {passive: true});
    });
})();
