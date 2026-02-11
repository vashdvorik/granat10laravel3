{{-- РЕДАКТИРОВАНИЕ МАГАЗИНА --}}

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

<div class="contents">
    <div class="container-fluid">
        @if(!$seller)
            @include('backend.cabinet.store.nostore')
        @else
            @php
                $activePackage = $seller->activePackage();
                $hasWebsiteLink = $activePackage && $activePackage->package && $activePackage->package->has_website_link;
                $hasSocialLinks = $activePackage && $activePackage->package && $activePackage->package->has_social_links;
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

            <form action="{{ route('account.store.update', $seller->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-lg-8">
                        {{-- Основная информация --}}
                        <div class="card mb-25">
                            <div class="card-header">
                                <h6 class="fw-500 mb-0">Основная информация</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Название магазина <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{ old('name', $seller->name) }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Телефон магазина</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                               id="phone" name="phone" value="{{ old('phone', $seller->phone) }}" 
                                               placeholder="+7 (___) ___-__-__">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="town" class="form-label">Город</label>
                                        <input type="text" class="form-control @error('town') is-invalid @enderror" 
                                               id="town" name="town" value="{{ old('town', $seller->town) }}">
                                        @error('town')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="sellerplace" class="form-label">Район</label>
                                        <input type="text" class="form-control @error('sellerplace') is-invalid @enderror" 
                                               id="sellerplace" name="sellerplace" value="{{ old('sellerplace', $seller->sellerplace) }}">
                                        @error('sellerplace')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="address" class="form-label">Адрес</label>
                                        <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                               id="address" name="address" value="{{ old('address', $seller->address) }}">
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="description" class="form-label">Описание магазина</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                                  id="description" name="description" rows="4">{{ old('description', $seller->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Контакты и соцсети --}}
                        <div class="card mb-25">
                            <div class="card-header">
                                <h6 class="fw-500 mb-0">Контакты и соцсети</h6>
                            </div>
                            <div class="card-body">
                                {{-- Предупреждение об ограничениях --}}
                                @if(!$hasWebsiteLink || !$hasSocialLinks)
                                    <div class="alert alert-warning d-flex align-items-center mb-3">
                                        <i class="uil uil-exclamation-triangle me-2"></i>
                                        <div>
                                            <strong>Ограничения тарифа:</strong>
                                            @if(!$hasWebsiteLink) Ссылка на сайт недоступна на вашем тарифе. @endif
                                            @if(!$hasSocialLinks) Подключение соцсетей недоступно на вашем тарифе. @endif
                
                                        </div>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    {{-- Веб-сайт --}}
                                    <div class="col-md-6">
                                        <label for="link" class="form-label">
                                            <i class="uil uil-globe"></i> Веб-сайт
                                            @if(!$hasWebsiteLink)
                                                <span class="badge badge-round badge-danger badge-lg">Недоступно</span>
                                            @endif
                                        </label>
                                        <input type="url" 
                                               class="form-control @error('link') is-invalid @enderror" 
                                               id="link" 
                                               name="link" 
                                               value="{{ old('link', $seller->link) }}"
                                               placeholder="https://example.com"
                                               @if(!$hasWebsiteLink) disabled @endif>
                                        @if(!$hasWebsiteLink)
                                            <small class="text-muted">Доступно на платных тарифах</small>
                                        @endif
                                        @error('link')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Google Maps --}}
                                    <div class="col-md-6">
                                        <label for="link_map" class="form-label">
                                            <i class="uil uil-map"></i> Google Maps
                                        </label>
                                        <input type="url" 
                                               class="form-control @error('link_map') is-invalid @enderror" 
                                               id="link_map" 
                                               name="link_map" 
                                               value="{{ old('link_map', $seller->link_map) }}"
                                               placeholder="https://maps.app.goo.gl/pP9mUgkPdA5BnkF25">
                                        <small class="text-muted">Доступно на всех тарифах</small>
                                        @error('link_map')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Telegram --}}
                                    <div class="col-md-4">
                                        <label for="telegram" class="form-label">
                                            <i class="uil uil-telegram"></i> Telegram
                                            @if(!$hasSocialLinks)
                                                <span class="badge badge-round badge-danger badge-lg">Недоступно</span>
                                            @endif
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('telegram') is-invalid @enderror" 
                                               id="telegram" 
                                               name="telegram" 
                                               value="{{ old('telegram', $seller->telegram) }}"
                                               placeholder="@username"
                                               @if(!$hasSocialLinks) disabled @endif>
                                        @error('telegram')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Instagram --}}
                                    <div class="col-md-4">
                                        <label for="instagram" class="form-label">
                                            <i class="uil uil-instagram"></i> Instagram
                                            @if(!$hasSocialLinks)
                                                <span class="badge badge-round badge-danger badge-lg">Недоступно</span>
                                            @endif
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('instagram') is-invalid @enderror" 
                                               id="instagram" 
                                               name="instagram" 
                                               value="{{ old('instagram', $seller->instagram) }}"
                                               placeholder="@username"
                                               @if(!$hasSocialLinks) disabled @endif>
                                        @error('instagram')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Viber --}}
                                    <div class="col-md-4">
                                        <label for="viber" class="form-label">
                                            <i class="uil uil-phone-volume"></i> Viber
                                            @if(!$hasSocialLinks)
                                                <span class="badge badge-round badge-danger badge-lg">Недоступно</span>
                                            @endif
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('viber') is-invalid @enderror" 
                                               id="viber" 
                                               name="viber" 
                                               value="{{ old('viber', $seller->viber) }}"
                                               placeholder="+7 (___) ___-__-__"
                                               @if(!$hasSocialLinks) disabled @endif>
                                        @error('viber')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Боковая панель --}}
                    <div class="col-lg-4">
                        <div class="card mb-25">
                            <div class="card-header">
                                <h6 class="fw-500">Действия</h6>
                            </div>
                            <div class="card-body">
                                <button type="submit" class="btn btn-primary w-100 mb-2">
                                    <i class="uil uil-save"></i> Сохранить изменения
                                </button>
                                <a href="{{ route('account.store.index') }}" class="btn btn-secondary w-100">
                                    <i class="uil uil-times"></i> Отмена
                                </a>
                            </div>
                        </div>

                        {{-- Текущий тариф --}}
                        @if($activePackage && $activePackage->package)
                            <div class="card mb-25">
                                <div class="card-header">
                                    <h6 class="fw-500">Текущий тариф</h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <h5 class="text-primary">{{ $activePackage->package->name }}</h5>
                                        @if($activePackage->package->price > 0)
                                            <div class="text-success fw-bold">{{ number_format($activePackage->package->price, 0) }} ₽</div>
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
            </form>
        @endif
    </div>
</div>
@endsection