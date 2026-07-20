(function () {
    'use strict';

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
        var id = String(button.dataset.bimmFavorite);
        var ids = [];
        try { ids = JSON.parse(localStorage.getItem('favorites') || '[]'); } catch (e) { ids = []; }
        ids = Array.isArray(ids) ? ids.map(String) : [];
        var index = ids.indexOf(id);
        if (index === -1) { ids.push(id); button.classList.add('is-active'); }
        else { ids.splice(index, 1); button.classList.remove('is-active'); }
        localStorage.setItem('favorites', JSON.stringify(ids));
    });
})();
