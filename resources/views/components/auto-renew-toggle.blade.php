@if(isset($sellerCabinetState) && $sellerCabinetState->hasActivePackage && ! $sellerCabinetState->packageIsLifetime)
    <div class="seller-cabinet-auto-renew">
        <div class="seller-cabinet-auto-renew__layout">
            <div class="seller-cabinet-auto-renew__copy">
                <strong class="seller-cabinet-auto-renew__title">Автопродление тарифа</strong>
                <small class="seller-cabinet-auto-renew__description">
                    @if($sellerCabinetState->autoRenewEnabled)
                        Тариф продлится автоматически при достаточном балансе.
                    @else
                        После окончания тарифа товары будут скрыты из каталога.
                    @endif
                </small>
            </div>
            <div class="seller-cabinet-auto-renew__control form-check form-switch">
                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="sellerAutoRenewToggle"
                    @checked($sellerCabinetState->autoRenewEnabled)
                >
                <label class="visually-hidden" for="sellerAutoRenewToggle">Автопродление тарифа</label>
            </div>
        </div>
        <small class="seller-cabinet-auto-renew__error text-danger d-none" id="sellerAutoRenewError">Не удалось сохранить настройку. Попробуйте еще раз.</small>
    </div>

    <script>
        (() => {
            const toggle = document.getElementById('sellerAutoRenewToggle');
            const error = document.getElementById('sellerAutoRenewError');

            if (!toggle || !error) {
                return;
            }

            toggle.addEventListener('change', async () => {
                const valueBeforeRequest = !toggle.checked;
                toggle.disabled = true;
                error.classList.add('d-none');

                try {
                    const response = await fetch('{{ route('account.store.toggle-auto-renew') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: new URLSearchParams({
                            auto_renew: toggle.checked ? '1' : '0'
                        })
                    });

                    if (!response.ok) {
                        throw new Error('Auto-renew setting was not saved');
                    }

                    window.location.reload();
                } catch (exception) {
                    toggle.checked = valueBeforeRequest;
                    error.classList.remove('d-none');
                    toggle.disabled = false;
                }
            });
        })();
    </script>
@endif
