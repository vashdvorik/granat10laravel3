{{-- Кнопка «Поблагодарить разработчика»: перевод с баланса кабинета администратору --}}
@inject('developerTip', 'App\Services\DeveloperTipService')

@if($developerTip->canTip($user))
    <div class="developer-tip mt-3 pt-3 border-top">
        <button type="button" class="btn btn-outline-warning btn-squared w-100"
                data-bs-toggle="collapse" data-bs-target="#developer-tip-form"
                aria-expanded="{{ $errors->has('amount') ? 'true' : 'false' }}" aria-controls="developer-tip-form">
            <i class="uil uil-heart"></i> Поблагодарить разработчика
        </button>

        <div class="collapse {{ $errors->has('amount') ? 'show' : '' }} mt-3" id="developer-tip-form">
            <form method="POST" action="{{ route('account.user.balance.tip') }}">
                @csrf
                <p class="text-muted fs-14 mb-2">
                    Сумма спишется с вашего баланса и будет переведена разработчику сервиса.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach($developerTip->presets() as $preset)
                        <button type="button" class="btn btn-outline-secondary btn-sm developer-tip__preset" data-amount="{{ $preset }}">
                            {{ $preset }} ₽
                        </button>
                    @endforeach
                </div>
                <input type="text" inputmode="decimal" name="amount" id="developer-tip-amount"
                       value="{{ old('amount') }}" autocomplete="off"
                       class="form-control mb-2 @error('amount') is-invalid @enderror"
                       placeholder="Сумма, ₽ ({{ $developerTip->minAmount() }}–{{ $developerTip->maxAmount() }})">
                @error('amount')
                    <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                @enderror
                <button type="submit" class="btn btn-warning btn-squared w-100">Отправить</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.developer-tip__preset').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('developer-tip-amount').value = btn.dataset.amount;
            });
        });
    </script>
@endif
