{{-- QR-код магазина: предпросмотр в стиле BIMM, скачивание (PNG/SVG) и печать --}}

@extends('backend.cabinet.layout_cabinet')

@section('title', 'QR-код магазина')

@section('content_cabinet')
<div class="contents shop-qr-page">
    <div class="container-fluid">
        @include('backend.cabinet.partials.flash')

        <div class="row">
            <div class="col-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">QR-код магазина</h4>
                </div>
            </div>
        </div>

        <div class="shop-qr" data-shop-qr data-shop-name="{{ $seller->name }}">
            <div class="shop-qr__stage">
                <div class="shop-qr__tabs" role="tablist" aria-label="Вариант для печати">
                    @foreach($layouts as $key => $layout)
                        <button type="button"
                                class="shop-qr__tab {{ $loop->first ? 'is-active' : '' }}"
                                role="tab"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                data-qr-tab="{{ $key }}">{{ $layout['title'] }}</button>
                    @endforeach
                </div>

                <div class="shop-qr__frame">
                    @foreach($layouts as $key => $layout)
                        <div class="shop-qr__preview shop-qr__preview--{{ $key }} {{ $loop->first ? '' : 'is-hidden' }}"
                             data-qr-preview="{{ $key }}"
                             data-qr-filename="{{ $layout['filename'] }}"
                             data-qr-width="{{ $layout['width'] }}"
                             data-qr-height="{{ $layout['height'] }}">
                            {!! $layout['svg'] !!}
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="shop-qr__side">
                <section class="shop-qr__card">
                    <h5>Скачайте и распечатайте</h5>
                    <p class="shop-qr__lead">
                        Покупатель наводит камеру телефона на код и сразу попадает в ваш магазин на BIMM —
                        ничего вводить не нужно.
                    </p>

                    @foreach($layouts as $key => $layout)
                        <p class="shop-qr__hint {{ $loop->first ? '' : 'is-hidden' }}" data-qr-hint="{{ $key }}">{{ $layout['hint'] }}</p>
                    @endforeach

                    <div class="shop-qr__actions">
                        <button type="button" class="shop-qr__btn shop-qr__btn--primary" data-qr-action="png">
                            <i class="uil uil-image-download"></i> Скачать PNG
                        </button>
                        <button type="button" class="shop-qr__btn" data-qr-action="print">
                            <i class="uil uil-print"></i> Распечатать
                        </button>
                        @foreach($layouts as $key => $layout)
                            <a class="shop-qr__btn {{ $loop->first ? '' : 'is-hidden' }}"
                               href="{{ route('account.store.qr.download', ['layout' => $key]) }}"
                               data-qr-svg="{{ $key }}">
                                <i class="uil uil-file-download-alt"></i> Скачать SVG
                            </a>
                        @endforeach
                    </div>
                    <p class="shop-qr__status" data-qr-status role="status" aria-live="polite"></p>
                    <p class="shop-qr__note">PNG подойдёт для Word и любого принтера. SVG — для типографии: он печатается чётко в любом размере.</p>
                </section>

                <section class="shop-qr__card">
                    <h5>Куда ведёт код</h5>
                    <a class="shop-qr__url" href="{{ $shopUrl }}" target="_blank" rel="noopener">{{ $displayUrl }}</a>
                    <div class="shop-qr__actions">
                        <button type="button" class="shop-qr__btn" data-qr-copy="{{ $shopUrl }}">
                            <i class="uil uil-copy"></i> Скопировать ссылку
                        </button>
                        <a class="shop-qr__btn" href="{{ $shopUrl }}" target="_blank" rel="noopener">
                            <i class="uil uil-external-link-alt"></i> Открыть магазин
                        </a>
                    </div>
                </section>

                <section class="shop-qr__card">
                    <h5>Чтобы код сканировался с первого раза</h5>
                    <ul class="shop-qr__tips">
                        <li>Печатайте код не меньше <strong>3 × 3 см</strong>, а на двери или витрине — от 8 × 8 см.</li>
                        <li>Не растягивайте картинку и не меняйте цвета: тёмный код на светлом фоне читается лучше всего.</li>
                        <li>Поверхность не должна бликовать — лучше матовая бумага или плёнка.</li>
                        <li>Распечатайте и проверьте камерой своего телефона, прежде чем клеить.</li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-shop-qr]');
    if (!root) { return; }

    var active = 'poster';
    var statusEl = root.querySelector('[data-qr-status]');

    function say(message, isError) {
        statusEl.textContent = message || '';
        statusEl.classList.toggle('is-error', !!isError);
    }

    function show(layout) {
        active = layout;
        root.querySelectorAll('[data-qr-tab]').forEach(function (tab) {
            var on = tab.getAttribute('data-qr-tab') === layout;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        root.querySelectorAll('[data-qr-preview]').forEach(function (el) {
            el.classList.toggle('is-hidden', el.getAttribute('data-qr-preview') !== layout);
        });
        root.querySelectorAll('[data-qr-hint]').forEach(function (el) {
            el.classList.toggle('is-hidden', el.getAttribute('data-qr-hint') !== layout);
        });
        root.querySelectorAll('[data-qr-svg]').forEach(function (el) {
            el.classList.toggle('is-hidden', el.getAttribute('data-qr-svg') !== layout);
        });
        say('');
    }

    function current() {
        return root.querySelector('[data-qr-preview="' + active + '"]');
    }

    function svgSource() {
        var svg = current().querySelector('svg').cloneNode(true);
        svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        return new XMLSerializer().serializeToString(svg);
    }

    function downloadPng() {
        var box = current();
        var width = parseInt(box.getAttribute('data-qr-width'), 10);
        var height = parseInt(box.getAttribute('data-qr-height'), 10);
        var filename = box.getAttribute('data-qr-filename');
        var scale = 3;

        say('Готовим файл…');

        var blob = new Blob([svgSource()], { type: 'image/svg+xml;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var image = new Image();

        image.onload = function () {
            try {
                var canvas = document.createElement('canvas');
                canvas.width = width * scale;
                canvas.height = height * scale;
                var ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(image, 0, 0, canvas.width, canvas.height);

                canvas.toBlob(function (png) {
                    URL.revokeObjectURL(url);
                    if (!png) { say('Не получилось собрать PNG. Скачайте SVG.', true); return; }
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(png);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    say('Файл сохранён: ' + filename);
                }, 'image/png');
            } catch (e) {
                URL.revokeObjectURL(url);
                say('Не получилось собрать PNG. Скачайте SVG.', true);
            }
        };
        image.onerror = function () {
            URL.revokeObjectURL(url);
            say('Не получилось собрать PNG. Скачайте SVG.', true);
        };
        image.src = url;
    }

    function printActive() {
        var poster = active === 'poster';
        var css = poster
            ? '@page{size:A4;margin:0}svg{display:block;width:210mm;height:297mm}'
            : '@page{size:A4;margin:20mm}body{text-align:center}svg{display:inline-block;width:100mm;height:auto}';
        var frame = document.createElement('iframe');
        frame.setAttribute('aria-hidden', 'true');
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        document.body.appendChild(frame);

        var doc = frame.contentWindow.document;
        doc.open();
        doc.write('<!doctype html><html><head><meta charset="utf-8"><title>QR-код магазина</title><style>html,body{margin:0;padding:0;background:#fff}' + css + '</style></head><body>' + svgSource() + '</body></html>');
        doc.close();

        setTimeout(function () {
            frame.contentWindow.focus();
            frame.contentWindow.print();
            setTimeout(function () { frame.remove(); }, 1500);
        }, 250);
    }

    function copyLink(button) {
        var link = button.getAttribute('data-qr-copy');
        var done = function () { say('Ссылка скопирована.'); };
        var fallback = function () {
            var input = document.createElement('input');
            input.value = link;
            document.body.appendChild(input);
            input.select();
            try { document.execCommand('copy'); done(); } catch (e) { say('Скопируйте ссылку вручную.', true); }
            input.remove();
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(link).then(done, fallback);
        } else {
            fallback();
        }
    }

    root.addEventListener('click', function (event) {
        var tab = event.target.closest('[data-qr-tab]');
        if (tab) { show(tab.getAttribute('data-qr-tab')); return; }

        var action = event.target.closest('[data-qr-action]');
        if (action) {
            if (action.getAttribute('data-qr-action') === 'png') { downloadPng(); } else { printActive(); }
            return;
        }

        var copy = event.target.closest('[data-qr-copy]');
        if (copy) { copyLink(copy); }
    });
})();
</script>
@endpush
