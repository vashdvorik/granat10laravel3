{{-- Компонент уведомления о статусе пакета --}}
@inject('sellerPackageStatusService', 'App\Services\SellerPackageStatusService')
@php
    $packageStatus = $sellerPackageStatusService->getStatus($seller);
@endphp

<div class="alert alert-{{ $packageStatus['alert_type'] }} alert-dismissible fade show mb-25" role="alert">
    <div class="d-flex align-items-start">
        <div class="alert-icon">
            @if($packageStatus['alert_type'] === 'danger')
                <i class="uil uil-exclamation-triangle" style="font-size: 24px;"></i>
            @elseif($packageStatus['alert_type'] === 'warning')
                <i class="uil uil-clock" style="font-size: 24px;"></i>
            @elseif($packageStatus['alert_type'] === 'success')
                <i class="uil uil-check-circle" style="font-size: 24px;"></i>
            @else
                <i class="uil uil-info-circle" style="font-size: 24px;"></i>
            @endif
        </div>
        <div class="ms-3 flex-grow-1">
            <h6 class="alert-heading mb-2">{{ $packageStatus['message'] }}</h6>
            
            @if(isset($packageStatus['description']))
                <p class="mb-2">{{ $packageStatus['description'] }}</p>
            @endif
            
            {{-- Если пакет просрочен --}}
            @if(!$packageStatus['has_package'])
                <div class="mt-3 d-flex gap-2 align-items-center flex-wrap">
                    <a href="{{ route('account.packages.index') }}" class="btn btn-sm btn-primary">
                        <i class="uil uil-shopping-cart"></i> Выбрать пакет
                    </a>
                    
                    @if($packageStatus['can_auto_renew'] ?? false)
                        <form action="{{ route('packages.auto-renew') }}" method="POST" class="d-inline-block">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="uil uil-refresh"></i> Продлить автоматически ({{ $packageStatus['price'] }} руб)
                            </button>
                        </form>
                    @else
                        <a href="{{ route('account.user.index') }}" class="btn btn-sm btn-warning"> 
                            <i class="uil uil-wallet"></i> Пополнить баланс
                        </a>
                    @endif
                </div>
            @endif
            
            {{-- Если скоро истекает и недостаточно денег --}}
            @if($seller->auto_renew && ($packageStatus['has_package'] ?? false) && ($packageStatus['days_left'] ?? 999) <= 7 && !($packageStatus['can_auto_renew'] ?? true))
                <div class="mt-3">
                    <a href="{{ route('account.user.index') }}" class="btn btn-sm btn-warning"> 
                        <i class="uil uil-wallet"></i> Пополнить баланс
                    </a>
                </div>
            @endif
            
        
        {{-- Управление автопродлением: только для пакетов с ограниченным сроком --}}
        @if(($packageStatus['has_package'] ?? false) && isset($packageStatus['end_date']))
            <div class="mt-3">
                <div class="form-check form-switch d-inline-block">
                    <input class="form-check-input" type="checkbox" 
                        id="autoRenewSwitch" 
                        {{ $seller->auto_renew ? 'checked' : '' }}
                        onchange="toggleAutoRenew(this)">
                    <label class="form-check-label" for="autoRenewSwitch">
                        Автоматическое продление
                    </label>
                </div>
                <small class="text-muted d-block mt-1" id="autoRenewStatus">
                    @if($seller->auto_renew)
                        ✅ При достаточном балансе пакет будет продлен автоматически
                    @else
                        ⚠️ Пакет будет отключен после {{ $packageStatus['end_date']->format('d.m.Y') }}, и все ваши товары не будут показываться покупателям.
                    @endif
                </small>
            </div>

            <script>
            function toggleAutoRenew(checkbox) {
                fetch('{{ route('account.store.toggle-auto-renew') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: 'auto_renew=' + (checkbox.checked ? '1' : '')
                }).then(() => {
                    location.reload();
                });
            }
            </script>
        @endif
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
