{{-- Редактирование магазина --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="contents store-edit-page">
    <div class="container-fluid">
        @if(!$seller)
            @include('backend.cabinet.store.nostore')
        @else
            @php
                $activePackage = $seller->activePackage();
                $hasWebsiteLink = $activePackage && $activePackage->package && $activePackage->package->has_website_link;
                $hasSocialLinks = $activePackage && $activePackage->package && $activePackage->package->has_social_links;
                $hasOrderSystem = $activePackage && $activePackage->package && $activePackage->package->has_order_system;
            @endphp

            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-main">
                        <h4 class="breadcrumb-title">Редактирование магазина</h4>
                        <div class="breadcrumb-action justify-content-center flex-wrap">
                            <a href="{{ route('account.store.index') }}" class="btn btn-sm btn-secondary">
                                <i class="uil uil-arrow-left"></i> Назад
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('account.store.update', $seller->id) }}" method="POST" enctype="multipart/form-data" class="store-edit-form">
                @csrf
                @method('PUT')

                <div class="row store-edit-layout">
                    <div class="col-lg-8 store-edit-main">
                        <div class="card mb-25 store-edit-card store-edit-main-card">
                            <div class="card-header">
                                <h6 class="fw-500 mb-0">Основная информация</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Название магазина <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                               id="name" name="name" value="{{ old('name', $seller->name) }}" maxlength="60" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Телефон магазина</label>
                                        <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                               id="phone" name="phone" value="{{ old('phone', $seller->phone) }}"
                                               maxlength="50"
                                               inputmode="numeric"
                                               pattern="[0-9]*"
                                               data-digits-only
                                               placeholder="79999999999">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="settlement_id" class="form-label">Населённый пункт</label>
                                        <select class="form-control @error('settlement_id') is-invalid @enderror"
                                                id="settlement_id" name="settlement_id" required>
                                            <option value="">Выберите населённый пункт</option>
                                            @foreach($regions as $region)
                                                <optgroup label="{{ $region->name }}">
                                                    @foreach($region->settlements as $settlement)
                                                        <option value="{{ $settlement->id }}" @selected((string) old('settlement_id', $seller->settlement_id) === (string) $settlement->id)>
                                                            {{ $settlement->name }} ({{ $settlement->type }})
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        @error('settlement_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="sellerplace" class="form-label">Район</label>
                                        <input type="text" class="form-control @error('sellerplace') is-invalid @enderror"
                                               id="sellerplace" name="sellerplace" value="{{ old('sellerplace', $seller->sellerplace) }}" maxlength="60">
                                        @error('sellerplace')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="address" class="form-label">Адрес</label>
                                        <input type="text" class="form-control @error('address') is-invalid @enderror"
                                               id="address" name="address" value="{{ old('address', $seller->address) }}" maxlength="1000" data-no-character-counter>
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="description" class="form-label">Описание магазина</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                                  id="description" name="description" rows="4" maxlength="2000">{{ old('description', $seller->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Заказы и доставка --}}
                        <div class="card mb-25 store-edit-card store-edit-orders-card">
                            <div class="card-header">
                                <h6 class="fw-500 mb-0">Заказы и доставка</h6>
                            </div>
                            <div class="card-body">
                                @if(!$hasOrderSystem)
                                    <div class="alert alert-warning">Текущий тариф не поддерживает заказы через сайт.</div>
                                @endif
                                <div class="form-check form-switch mb-3">
                                    <input type="hidden" name="can_order" value="0">
                                    <input class="form-check-input @error('can_order') is-invalid @enderror"
                                           type="checkbox" id="can_order" name="can_order" value="1"
                                           @checked(old('can_order', $seller->can_order))
                                           @disabled(!$hasOrderSystem)>
                                    <label class="form-check-label" for="can_order">Принимать заказы через сайт</label>
                                    <small class="text-muted d-block">На карточках разрешённых товаров появится форма заказа.</small>
                                    @error('can_order')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input type="hidden" name="delivery_enabled" value="0">
                                    <input class="form-check-input" type="checkbox"
                                           id="delivery_enabled" name="delivery_enabled" value="1"
                                           @checked(old('delivery_enabled', $seller->delivery_enabled))
                                           @disabled(!$hasOrderSystem)>
                                    <label class="form-check-label" for="delivery_enabled">Предлагать доставку</label>
                                    <small class="text-muted d-block">Доставка появится только у товаров, где она также разрешена.</small>
                                </div>
                                <label for="delivery_terms" class="form-label">Условия доставки</label>
                                <textarea class="form-control @error('delivery_terms') is-invalid @enderror"
                                          id="delivery_terms" name="delivery_terms" rows="4" maxlength="2000"
                                          @disabled(!$hasOrderSystem)>{{ old('delivery_terms', $seller->delivery_terms) }}</textarea>
                                <small class="text-muted">Например: районы доставки, стоимость и сроки.</small>
                                @error('delivery_terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <hr>
                                <h6>Telegram-уведомления</h6>
                                @if($seller->telegram_verified_at)
                                    <p class="text-success mb-2">
                                        Telegram подключён {{ $seller->telegram_verified_at->format('d.m.Y H:i') }}.
                                    </p>
                                    <button type="submit" form="store-telegram-disconnect-form" class="btn btn-outline-danger btn-sm">Отключить Telegram</button>
                                @else
                                    <p class="text-muted">
                                        После подключения бот будет присылать сообщения о новых заказах.
                                    </p>
                                    <button type="submit" form="store-telegram-connect-form" class="btn btn-outline-primary btn-sm">Подключить Telegram</button>
                                @endif
                            </div>
                        </div>

                        {{-- Контакты и соцсети --}}
                        <div class="card mb-25 store-edit-card store-edit-contacts-card">
                            <div class="card-header">
                                <h6 class="fw-500 mb-0">Контакты и соцсети</h6>
                            </div>
                            <div class="card-body">
                                {{-- Предупреждение об ограничениях --}}
                                @include('backend.cabinet.partials.store-feature-grid', [
                                    'seller' => $seller,
                                    'sellerCabinetState' => $sellerCabinetState ?? null,
                                ])


                                <div class="store-edit-visibility-note">
                                    <i class="uil uil-info-circle"></i>
                                    <div>
                                        <strong>Заполнить можно сейчас.</strong>
                                        <span>Google Maps показывается всегда. Сайт и соцсети сохраняются в профиле, но покупатели увидят их только на тарифе, где эти возможности доступны.</span>
                                    </div>
                                </div>

                                <div class="store-edit-contact-group">
                                    <div class="store-edit-contact-group__head">
                                        <h6>Ссылки магазина</h6>
                                        <p>Адрес на карте помогает покупателю понять, где находится продавец. Сайт показывается по тарифу.</p>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="link_map" class="form-label">
                                                <i class="uil uil-map"></i> Google Maps
                                            </label>
                                            <input type="url"
                                                   class="form-control @error('link_map') is-invalid @enderror"
                                                   id="link_map"
                                                   name="link_map"
                                                   value="{{ old('link_map', $seller->link_map) }}"
                                                   maxlength="2000"
                                                   placeholder="https://maps.app.goo.gl/pP9mUgkPdA5BnkF25">
                                            <small class="text-muted">Показывается покупателям на всех тарифах.</small>
                                            @error('link_map')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="link" class="form-label">
                                                <i class="uil uil-globe"></i> Веб-сайт
                                            </label>
                                            <input type="url"
                                                   class="form-control @error('link') is-invalid @enderror"
                                                   id="link"
                                                   name="link"
                                                   value="{{ old('link', $seller->link) }}"
                                                   maxlength="191"
                                                   placeholder="https://example.com">
                                            <small class="text-muted">
                                                @if($hasWebsiteLink)
                                                    Показывается покупателям на текущем тарифе.
                                                @else
                                                    Можно сохранить сейчас; покупатели увидят сайт после смены тарифа.
                                                @endif
                                            </small>
                                            @error('link')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="store-edit-contact-group">
                                    <div class="store-edit-contact-group__head">
                                        <h6>Соцсети и мессенджеры</h6>
                                        <p>Эти поля нужны для связи с магазином. Они не отключаются при заполнении, но публичная видимость зависит от тарифа.</p>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="telegram" class="form-label">
                                                <i class="uil uil-telegram"></i> Telegram
                                            </label>
                                            <input type="text"
                                                   class="form-control @error('telegram') is-invalid @enderror"
                                                   id="telegram"
                                                   name="telegram"
                                                   value="{{ old('telegram', $seller->telegram) }}"
                                                   maxlength="255"
                                                   placeholder="@username">
                                            @error('telegram')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label for="instagram" class="form-label">
                                                <i class="uil uil-instagram"></i> Instagram
                                            </label>
                                            <input type="text"
                                                   class="form-control @error('instagram') is-invalid @enderror"
                                                   id="instagram"
                                                   name="instagram"
                                                   value="{{ old('instagram', $seller->instagram) }}"
                                                   maxlength="255"
                                                   placeholder="@username">
                                            @error('instagram')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label for="viber" class="form-label">
                                                <i class="uil uil-phone-volume"></i> Viber
                                            </label>
                                            <input type="text"
                                                   class="form-control @error('viber') is-invalid @enderror"
                                                   id="viber"
                                                   name="viber"
                                                   value="{{ old('viber', $seller->viber) }}"
                                                   maxlength="255"
                                                   placeholder="+7 (___) ___-__-__">
                                            @error('viber')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    @unless($hasSocialLinks)
                                        <div class="store-edit-contact-note">Соцсети сохранятся в кабинете, но будут скрыты от покупателей до смены тарифа.</div>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Боковая панель --}}
                    <div class="col-lg-4 store-edit-aside">
                        {{-- Фото / логотип магазина --}}
                        <div class="card mb-25 store-edit-card store-edit-logo-card">
                            <div class="card-header">
                                <h6 class="fw-500">Фото магазина</h6>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    @if($seller->photo)
                                        <img id="logo-preview" src="{{ Storage::url($seller->photo) }}"
                                             class="rounded-circle wh-120 object-fit-cover border"
                                             alt="Логотип">
                                    @else
                                        <img id="logo-preview" src="{{ asset('cabinet/img/author/profile.png') }}"
                                             class="rounded-circle wh-120 object-fit-cover border"
                                             alt="Логотип">
                                    @endif
                                </div>
                                <label for="shop-logo-upload" class="btn btn-outline-secondary btn-sm w-100 mb-0">
                                    <i class="uil uil-upload"></i> Загрузить фото
                                </label>
                                <input id="shop-logo-upload" type="file" name="logo" accept="image/*" class="d-none">
                                <small class="text-muted d-block mt-2">JPEG, PNG, JPG, WebP &mdash; макс. 5&nbsp;МБ</small>
                                @error('logo')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        @if($activePackage && $activePackage->package)
                            <div class="card mb-25 store-edit-card store-edit-tariff-card">
                                <div class="card-header">
                                    <h6 class="fw-500">Текущий тариф</h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <h5 class="text-primary">{{ $activePackage->package->name }}</h5>
                                        @if($activePackage->package->price > 0)
                                            <div class="text-success fw-bold">{{ number_format($activePackage->package->price, 0) }} руб.</div>
                                        @else
                                            <div class="text-muted">Бесплатно</div>
                                        @endif
                                    </div>

                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item px-0 py-2 border-0">
                                            @if($activePackage->package->has_website_link)
                                                <i class="uil uil-check-circle text-success"></i>
                                                <small class="text-success">Ссылка на сайт</small>
                                            @else
                                                <i class="uil uil-times-circle text-muted"></i>
                                                <small class="text-muted">Ссылка на сайт</small>
                                            @endif
                                        </div>

                                        <div class="list-group-item px-0 py-2 border-0">
                                            @if($activePackage->package->has_social_links)
                                                <i class="uil uil-check-circle text-success"></i>
                                                <small class="text-success">Соцсети</small>
                                            @else
                                                <i class="uil uil-times-circle text-muted"></i>
                                                <small class="text-muted">Соцсети</small>
                                            @endif
                                        </div>
                                    </div>

                                    @if(!$hasWebsiteLink || !$hasSocialLinks)
                                        <a href="{{ route('account.packages.index') }}" class="btn btn-warning btn-sm w-100 mt-3">
                                            <i class="uil uil-arrow-circle-up"></i> Обновить тариф
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="store-edit-submit-bar">
                    <a href="{{ route('account.store.index') }}" class="btn btn-secondary">
                        <i class="uil uil-times"></i> Отмена
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="uil uil-save"></i> Сохранить изменения
                    </button>
                </div>
            </form>

            @if($seller->telegram_verified_at)
                <form id="store-telegram-disconnect-form" method="POST" action="{{ route('account.store.telegram.disconnect') }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @else
                <form id="store-telegram-connect-form" method="POST" action="{{ route('account.store.telegram.connect') }}" class="d-none">
                    @csrf
                </form>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('shop-logo-upload').addEventListener('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (event) {
            document.getElementById('logo-preview').src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
</script>
@endpush
