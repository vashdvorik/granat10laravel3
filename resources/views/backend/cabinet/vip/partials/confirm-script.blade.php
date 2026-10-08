{{-- Подтверждение покупки/отключения VIP. Подключается один раз на странице. --}}
@once
<script>
(function () {
    function ask(title, text, confirmLabel, danger, done) {
        if (window.Swal) {
            Swal.fire({
                title: title,
                text: text,
                icon: danger ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: confirmLabel,
                cancelButtonText: 'Отмена',
                confirmButtonColor: danger ? '#dc3545' : '#f5a623',
            }).then(function (result) { if (result.isConfirmed) { done(); } });
        } else if (window.confirm(title + '\n' + text)) {
            done();
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!form.matches || !form.matches('[data-vip-form]') || form.dataset.vipConfirmed === '1') {
            return;
        }

        event.preventDefault();

        if (form.dataset.vipForm === 'cancel') {
            ask('Отключить VIP?', form.dataset.confirmText, 'Отключить VIP', true, function () {
                form.dataset.vipConfirmed = '1';
                form.submit();
            });

            return;
        }

        var option = form.querySelector('input[name="days"]:checked') || form.querySelector('select[name="days"] option:checked');

        if (!option) {
            return;
        }

        ask(
            form.dataset.extend === '1' ? 'Продлить VIP?' : 'Включить VIP?',
            'Спишем ' + option.dataset.price + ' с баланса. VIP для «' + form.dataset.product + '» будет действовать до ' + option.dataset.until + '.',
            'Оплатить ' + option.dataset.price,
            false,
            function () {
                form.dataset.vipConfirmed = '1';
                form.submit();
            }
        );
    });
})();
</script>
@endonce
