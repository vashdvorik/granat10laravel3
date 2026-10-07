(function () {
    'use strict';

    function getFavoriteIds() {
        var ids = [];
        try { ids = JSON.parse(localStorage.getItem('favorites') || '[]'); } catch (e) { ids = []; }
        return Array.isArray(ids) ? ids.map(String) : [];
    }

    function syncFavoriteButtons() {
        var ids = getFavoriteIds();
        document.querySelectorAll('[data-bimm-favorite]').forEach(function (button) {
            var active = ids.indexOf(String(button.dataset.bimmFavorite)) !== -1;
            button.classList.toggle('is-active', active);
            var icon = button.querySelector('i');
            if (icon) { icon.classList.toggle('fa-solid', active); icon.classList.toggle('fa-regular', !active); }
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.setAttribute('aria-label', active ? 'Удалить из избранного' : 'Добавить в избранное');
        });
    }

    syncFavoriteButtons();
    window.addEventListener('bimm:favorites-rendered', syncFavoriteButtons);

    var toggle = document.querySelector('[data-bimm-menu-toggle]');
    var mobileNav = document.querySelector('[data-bimm-mobile-nav]');

    if (toggle && mobileNav) {
        toggle.addEventListener('click', function () {
            var open = mobileNav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    document.querySelectorAll('[data-bimm-gallery-thumb]').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var image = document.querySelector('[data-bimm-gallery-main] img');
            if (!image) return;
            image.src = thumb.dataset.src;
            document.querySelectorAll('[data-bimm-gallery-thumb]').forEach(function (item) {
                item.classList.remove('is-active');
            });
            thumb.classList.add('is-active');
        });
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-bimm-favorite]');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        var id = String(button.dataset.bimmFavorite);
        var ids = getFavoriteIds();
        var index = ids.indexOf(id);
        if (index === -1) { ids.push(id); button.classList.add('is-active'); }
        else { ids.splice(index, 1); button.classList.remove('is-active'); }
        var active = index === -1;
        var icon = button.querySelector('i');
        if (icon) { icon.classList.toggle('fa-solid', active); icon.classList.toggle('fa-regular', !active); }
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.setAttribute('aria-label', active ? 'Удалить из избранного' : 'Добавить в избранное');
        localStorage.setItem('favorites', JSON.stringify(ids));
    });
})();
