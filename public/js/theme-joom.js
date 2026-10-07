(function () {
    'use strict';

    function getFavoriteIds() {
        var ids = [];
        try { ids = JSON.parse(localStorage.getItem('favorites') || '[]'); } catch (error) { ids = []; }
        return Array.isArray(ids) ? ids.map(String) : [];
    }

    function setFavoriteState(button, active) {
        var icon = button.querySelector('i');
        button.classList.toggle('is-active', active);
        if (icon) {
            icon.classList.toggle('fa-solid', active);
            icon.classList.toggle('fa-regular', !active);
        }
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.setAttribute('aria-label', active ? 'Удалить из избранного' : 'Добавить в избранное');
    }

    function syncFavoriteButtons() {
        var ids = getFavoriteIds();
        document.querySelectorAll('[data-joom-favorite]').forEach(function (button) {
            setFavoriteState(button, ids.indexOf(String(button.dataset.joomFavorite)) !== -1);
        });
    }

    syncFavoriteButtons();

    var menuToggle = document.querySelector('[data-joom-menu-toggle]');
    var mobileMenu = document.querySelector('[data-joom-mobile-menu]');
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function () {
            var open = mobileMenu.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    var categoriesToggle = document.querySelector('[data-joom-categories-toggle]');
    if (categoriesToggle) {
        categoriesToggle.addEventListener('click', function () {
            categoriesToggle.setAttribute('aria-expanded', categoriesToggle.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');
        });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-joom-favorite]');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();

        var id = String(button.dataset.joomFavorite);
        var ids = getFavoriteIds();
        var index = ids.indexOf(id);
        var active = index === -1;
        if (active) ids.push(id); else ids.splice(index, 1);
        localStorage.setItem('favorites', JSON.stringify(ids));
        setFavoriteState(button, active);
    });

    document.querySelectorAll('[data-joom-gallery-thumb]').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            var image = document.querySelector('[data-joom-gallery-main] img');
            if (!image) return;
            image.src = thumb.dataset.src;
            document.querySelectorAll('[data-joom-gallery-thumb]').forEach(function (item) { item.classList.remove('is-active'); });
            thumb.classList.add('is-active');
        });
    });
})();
