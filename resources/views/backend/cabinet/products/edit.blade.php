{{-- РЕДАКТИРОВАНИЕ ТОВАРА --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

<div class="contents">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main">
                    <h4 class="text-capitalize breadcrumb-title">Редактировать товар</h4>
                    <div class="breadcrumb-action justify-content-center flex-wrap">
                        <a href="{{ route('account.store.products.index') }}" class="btn btn-sm btn-secondary">
                            <i class="uil uil-arrow-left"></i> Назад
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-add global-shadow px-sm-30 py-sm-50 px-0 py-20 bg-white radius-xl w-100 mb-40">
                    <div class="row justify-content-center">
                        <div class="col-xxl-7 col-lg-10">
                            <div class="mx-sm-30 mx-20">
                                <form action="{{ route('account.store.products.update', $product) }}" 
                                      method="POST" 
                                      enctype="multipart/form-data"
                                      novalidate>
                                    @csrf
                                    @method('PUT')

                                    {{-- Основная информация --}}
                                    <div class="card add-product p-sm-30 p-20 mb-30">
                                        <div class="card-body p-0">
                                            <div class="card-header">
                                                <h6 class="fw-500">Информация о товаре</h6>
                                            </div>

                                            {{-- ============================================ --}}
                                            {{-- БЛОК СООБЩЕНИЙ И ОШИБОК --}}
                                            {{-- ============================================ --}}
                                            <div class="px-sm-40 px-20 pt-20">
                                                {{-- ✅ СООБЩЕНИЕ ОБ УСПЕХЕ --}}
                                                @if(session('success'))
                                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                        <div class="alert-content">
                                                            <p>{{ session('success') }}</p>
                                                            <button type="button" class="btn-close text-capitalize" data-bs-dismiss="alert" aria-label="Close">
                                                                <img src="{{ asset('cabinet/img/svg/x.svg') }}" alt="x" class="svg" aria-hidden="true">
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- ✅ СООБЩЕНИЕ ОБ ОШИБКЕ --}}
                                                @if(session('error'))
                                                    <div class="alert-big alert alert-danger alert-dismissible fade show" role="alert">
                                                        <div class="alert-content">
                                                            <h6 class="alert-heading">Ошибка</h6>
                                                            <p>{{ session('error') }}</p>
                                                            <button type="button" class="btn-close text-capitalize" data-bs-dismiss="alert" aria-label="Close">
                                                                <img src="{{ asset('cabinet/img/svg/x.svg') }}" alt="x" class="svg" aria-hidden="true">
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- ✅ ОШИБКИ ВАЛИДАЦИИ (включая лимиты пакета) --}}
                                                @if($errors->any())
                                                    <div class="alert-big alert alert-danger alert-dismissible fade show" role="alert">
                                                        <div class="alert-content">
                                                            <h6 class="alert-heading">Ошибка при сохранении товара</h6>
                                                            <ul class="mb-0">
                                                                @foreach($errors->all() as $error)
                                                                    <li>{{ $error }}</li>
                                                                @endforeach
                                                            </ul>
                                                            <button type="button" class="btn-close text-capitalize" data-bs-dismiss="alert" aria-label="Close">
                                                                <img src="{{ asset('cabinet/img/svg/x.svg') }}" alt="x" class="svg" aria-hidden="true">
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="add-product__body px-sm-40 px-20">
                                                {{-- Название --}}
                                                <div class="form-group">
                                                    <label for="name">Название товара <span class="text-danger">*</span></label>
                                                    <input type="text" 
                                                           class="form-control @error('name') is-invalid @enderror" 
                                                           id="name" 
                                                           name="name" 
                                                           value="{{ old('name', $product->name) }}" 
                                                           placeholder="Введите название товара"
                                                           required>
                                                    @error('name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Категория --}}
                                                <div class="form-group">
                                                    <label for="category_id">Категория <span class="text-danger">*</span></label>
                                                    <select class="form-control @error('category_id') is-invalid @enderror" 
                                                            id="category_id" 
                                                            name="category_id" 
                                                            required>
                                                        <option value="">Выберите категорию</option>
                                                        @foreach($categories as $category)
                                                            <optgroup label="{{ $category->name }}">
                                                                @foreach($category->children as $child)
                                                                    <option value="{{ $child->id }}" 
                                                                            {{ old('category_id', $product->category_id) == $child->id ? 'selected' : '' }}>
                                                                        {{ $child->name }}
                                                                    </option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endforeach
                                                    </select>
                                                    @error('category_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Цена --}}
                                                <div class="form-group">
                                                    <label>Цена <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <input type="number" 
                                                               class="form-control @error('price') is-invalid @enderror" 
                                                               name="price" 
                                                               value="{{ old('price', $product->price) }}" 
                                                               min="0" 
                                                               step="1"
                                                               placeholder="Введите цену"
                                                               required>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">₽</span>
                                                        </div>
                                                    </div>
                                                    @error('price')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Цена со скидкой --}}
                                                <div class="form-group">
                                                    <label>Цена со скидкой</label>
                                                    <div class="input-group">
                                                        <input type="number" 
                                                               class="form-control @error('lowprice') is-invalid @enderror" 
                                                               name="lowprice" 
                                                               value="{{ old('lowprice', $product->lowprice) }}" 
                                                               min="0" 
                                                               step="1"
                                                               placeholder="Не указана">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">₽</span>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">Если указана, будет показана как скидочная цена</small>
                                                    @error('lowprice')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Статус активности --}}
                                                <div class="form-group mb-20">
                                                    <label class="form-label">Статус активности</label>
                                                    <div class="d-flex gap-3">
                                                        <div class="radio-horizontal-list d-flex flex-wrap gap-3">
                                                            <div class="radio-theme-default custom-radio">
                                                                <input class="radio @error('is_active') is-invalid @enderror" 
                                                                       type="radio" 
                                                                       name="is_active" 
                                                                       value="1" 
                                                                       id="active-yes"
                                                                       {{ old('is_active', $product->is_active) == '1' ? 'checked' : '' }}>
                                                                <label for="active-yes">
                                                                    <span class="radio-text">Активен</span>
                                                                </label>
                                                            </div>
                                                            <div class="radio-theme-default custom-radio">
                                                                <input class="radio @error('is_active') is-invalid @enderror" 
                                                                       type="radio" 
                                                                       name="is_active" 
                                                                       value="0" 
                                                                       id="active-no"
                                                                       {{ old('is_active', $product->is_active) == '0' ? 'checked' : '' }}>
                                                                <label for="active-no">
                                                                    <span class="radio-text">Неактивен</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    {{-- ⚠️ Ошибка лимита АКТИВНЫХ товаров --}}
                                                    @error('is_active')
                                                        <div class="alert alert-warning d-flex align-items-start py-2 px-3 mt-2 mb-0" style="border-left: 4px solid #FA8B0C;">
                                                            <i class="uil uil-exclamation-triangle me-2 mt-1" style="font-size: 18px; color: #FA8B0C;"></i>
                                                            <div>
                                                                <small class="d-block mb-0 text-dark">
                                                                    <strong>{{ $message }}</strong>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    @enderror
                                                </div>

                                                {{-- ✅ VIP ТОВАР С ИНФОРМАЦИОННЫМ БЛОКОМ --}}
                                                <div class="form-group mb-20">
                                                    <div class="checkbox-theme-default custom-checkbox">
                                                        <input class="checkbox" 
                                                               type="checkbox" 
                                                               name="is_vip" 
                                                               value="1" 
                                                               id="is_vip"
                                                               {{ old('is_vip', $product->is_vip) ? 'checked' : '' }}>
                                                        <label for="is_vip">
                                                            <span class="checkbox-text">
                                                                <i class="uil uil-star text-warning"></i> VIP товар
                                                            </span>
                                                        </label>
                                                    </div>
                                                    
                                                    {{-- ℹ️ Информационное сообщение --}}
                                                    <div class="alert alert-info d-flex align-items-start py-2 px-3 mt-2 mb-0" style="border-left: 4px solid #5F63F2;">
                                                        <i class="uil uil-info-circle me-2 mt-1" style="font-size: 18px; color: #5F63F2;"></i>
                                                        <div>
                                                            <small class="d-block mb-0">
                                                                <strong>VIP товары показываются всегда первыми</strong> в каталоге и в поиске
                                                            </small>
                                                        </div>
                                                    </div>

                                                    {{-- ⚠️ Ошибка лимита VIP товаров --}}
                                                    @error('is_vip')
                                                        <div class="alert alert-warning d-flex align-items-start py-2 px-3 mt-2 mb-0" style="border-left: 4px solid #FA8B0C;">
                                                            <i class="uil uil-exclamation-triangle me-2 mt-1" style="font-size: 18px; color: #FA8B0C;"></i>
                                                            <div>
                                                                <small class="d-block mb-0 text-dark">
                                                                    <strong>{{ $message }}</strong>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    @enderror
                                                </div>

                                                {{-- Описание --}}
                                                <div class="form-group">
                                                    <label for="desc">Описание товара</label>
                                                    <textarea class="form-control @error('desc') is-invalid @enderror" 
                                                              id="desc" 
                                                              name="desc" 
                                                              rows="4" 
                                                              placeholder="Подробное описание товара">{{ old('desc', $product->desc) }}</textarea>
                                                    @error('desc')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Характеристики --}}
                                                <div class="form-group">
                                                    <label for="features1">Характеристика 1</label>
                                                    <input type="text" 
                                                           class="form-control @error('features1') is-invalid @enderror" 
                                                           id="features1" 
                                                           name="features1" 
                                                           value="{{ old('features1', $product->features1) }}" 
                                                           placeholder="Например: Цвет: Красный">
                                                    @error('features1')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="features2">Характеристика 2</label>
                                                    <input type="text" 
                                                           class="form-control @error('features2') is-invalid @enderror" 
                                                           id="features2" 
                                                           name="features2" 
                                                           value="{{ old('features2', $product->features2) }}" 
                                                           placeholder="Например: Размер: L">
                                                    @error('features2')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="features3">Характеристика 3</label>
                                                    <input type="text" 
                                                           class="form-control @error('features3') is-invalid @enderror" 
                                                           id="features3" 
                                                           name="features3" 
                                                           value="{{ old('features3', $product->features3) }}" 
                                                           placeholder="Например: Материал: Хлопок">
                                                    @error('features3')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group mb-0">
                                                    <label for="features4">Характеристика 4</label>
                                                    <input type="text" 
                                                           class="form-control @error('features4') is-invalid @enderror" 
                                                           id="features4" 
                                                           name="features4" 
                                                           value="{{ old('features4', $product->features4) }}" 
                                                           placeholder="Например: Вес: 500г">
                                                    @error('features4')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Кнопки --}}
                                    <div class="button-group add-product-btn d-flex justify-content-sm-end justify-content-center mt-40 gap-2">
                                        <a href="{{ route('account.store.products.index') }}" 
                                           class="btn btn-light btn-default btn-squared fw-400 text-capitalize">
                                            Отмена
                                        </a>
                                        <button type="submit" 
                                                class="btn btn-primary btn-default btn-squared text-capitalize">
                                            <i class="uil uil-save"></i> Сохранить изменения
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection