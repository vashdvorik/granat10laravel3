{{-- ДОБАВЛЕНИЕ ТОВАРА --}}

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
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main">
                    <h4 class="text-capitalize breadcrumb-title">Добавить товар</h4>
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
                                <form action="{{ route('account.store.products.store') }}" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    {{-- Основная информация --}}
                                    <div class="card add-product p-sm-30 p-20 mb-30">
                                        <div class="card-body p-0">
                                            <div class="card-header">
                                                <h6 class="fw-500">Информация о товаре</h6>
                                            </div>
                                            <div class="add-product__body px-sm-40 px-20">
                                                {{-- Название --}}
                                                <div class="form-group">
                                                    <label for="name">Название товара <span class="text-danger">*</span></label>
                                                    <input type="text" 
                                                           class="form-control @error('name') is-invalid @enderror" 
                                                           id="name" 
                                                           name="name" 
                                                           value="{{ old('name') }}" 
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
                                                                            {{ old('category_id') == $child->id ? 'selected' : '' }}>
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
                                                               value="{{ old('price', 0) }}" 
                                                               min="0" 
                                                               step="1"
                                                               placeholder="0"
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
                                                               value="{{ old('lowprice') }}" 
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

                                                {{-- Статус --}}
                                                <div class="form-group mb-20">
                                                    <label class="mb-15">Статус</label>
                                                    <div class="d-flex">
                                                        <div class="radio-horizontal-list d-flex flex-wrap gap-3">
                                                            <div class="radio-theme-default custom-radio">
                                                                <input class="radio" 
                                                                       type="radio" 
                                                                       name="is_active" 
                                                                       value="1" 
                                                                       id="active-yes"
                                                                       {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                                                <label for="active-yes">
                                                                    <span class="radio-text">Активен</span>
                                                                </label>
                                                            </div>
                                                            <div class="radio-theme-default custom-radio">
                                                                <input class="radio" 
                                                                       type="radio" 
                                                                       name="is_active" 
                                                                       value="0" 
                                                                       id="active-no"
                                                                       {{ old('is_active') == '0' ? 'checked' : '' }}>
                                                                <label for="active-no">
                                                                    <span class="radio-text">Неактивен</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- VIP товар --}}
                                                <div class="form-group mb-20">
                                                    <div class="checkbox-theme-default custom-checkbox">
                                                        <input class="checkbox" 
                                                               type="checkbox" 
                                                               name="is_vip" 
                                                               value="1" 
                                                               id="is_vip"
                                                               {{ old('is_vip') ? 'checked' : '' }}>
                                                        <label for="is_vip">
                                                            <span class="checkbox-text">
                                                                <i class="uil uil-star text-warning"></i> VIP товар
                                                            </span>
                                                        </label>
                                                    </div>
                                                    <small class="text-muted">VIP товары выделяются в каталоге</small>
                                                </div>

                                                {{-- Описание --}}
                                                <div class="form-group">
                                                    <label for="desc">Описание товара</label>
                                                    <textarea class="form-control @error('desc') is-invalid @enderror" 
                                                              id="desc" 
                                                              name="desc" 
                                                              rows="4" 
                                                              placeholder="Подробное описание товара">{{ old('desc') }}</textarea>
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
                                                           value="{{ old('features1') }}" 
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
                                                           value="{{ old('features2') }}" 
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
                                                           value="{{ old('features3') }}" 
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
                                                           value="{{ old('features4') }}" 
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
                                            <i class="uil uil-save"></i> Сохранить товар
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