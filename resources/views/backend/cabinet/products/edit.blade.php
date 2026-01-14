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
                                    
                                    {{-- Изображения товара --}}
                                    <div class="card add-product p-sm-30 p-20 mb-30">
                                        <div class="card-body p-0">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h6 class="fw-500">Изображения товара</h6>
                                                <span class="badge badge-light">{{ $product->images->count() }}/5</span>
                                            </div>
                                            
                                            <div class="add-product__body-img px-sm-40 px-20">
                                                {{-- Существующие изображения --}}
                                                @if($product->images->count() > 0)
                                                    <div class="existing-images mb-25">
                                                        <h6 class="fw-500 mb-15">Текущие изображения</h6>
                                                        <div class="row g-3" id="existing-images-container">
                                                            @foreach($product->images->sortByDesc('is_main') as $image)
                                                                <div class="col-md-6" data-image-id="{{ $image->id }}">
                                                                    <div class="card border">
                                                                        <div class="card-body p-3">
                                                                            <div class="d-flex align-items-start gap-3">
                                                                                <img src="{{ asset('storage/' . $image->path) }}" 
                                                                                     alt="Product image" 
                                                                                     class="rounded"
                                                                                     style="width: 100px; height: 100px; object-fit: cover;">
                                                                                <div class="flex-grow-1">
                                                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                                                        <div>
                                                                                            @if($image->is_main)
                                                                                                <span class="btn btn-light btn-default btn-squared">Главное</span>
                                                                                            @endif
                                                                                            <p class="mb-0 text-muted small">{{ basename($image->path) }}</p>
                                                                                        </div>
                                                                                        <button type="button" 
                                                                                                class="btn btn-sm btn-outline-danger delete-existing-image"
                                                                                                data-image-id="{{ $image->id }}"
                                                                                                title="Удалить">
                                                                                            <i class="uil uil-trash-alt"></i>
                                                                                        </button>
                                                                                    </div>
                                                                                    @if(!$image->is_main && $product->images->count() > 1)
                                                                                        <button type="button" 
                                                                                                class="btn btn-sm btn-outline-primary set-main-image"
                                                                                                data-image-id="{{ $image->id }}">
                                                                                            <i class="uil uil-star"></i> Сделать главным
                                                                                        </button>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Загрузка новых изображений --}}
                                                @if($product->images->count() < 5)
                                                    <div class="new-images-upload">
                                                        <h6 class="fw-500 mb-15">Добавить новые изображения</h6>
                                                        <label for="upload-images" class="file-upload__label">
                                                            <span class="upload-product-img px-10 d-block">
                                                                <span class="file-upload">
                                                                    <img class="svg" src="{{ asset('cabinet/img/svg/upload.svg') }}" alt="">
                                                                    <input id="upload-images" 
                                                                           class="file-upload__input" 
                                                                           type="file" 
                                                                           name="images[]" 
                                                                           multiple 
                                                                           accept="image/jpeg,image/png,image/jpg,image/webp">
                                                                </span>
                                                                <span class="pera">Перетащите изображения сюда</span>
                                                                
                                                                <span>или <a href="javascript:void(0)" class="color-secondary" onclick="document.getElementById('upload-images').click()">Выберите файлы</a> для загрузки</span>
                                                                <span class="d-block mt-10 fs-13 text-muted">
                                                                    (JPG, PNG, WebP • макс. 5 МБ каждое • ещё {{ 5 - $product->images->count() }} фото)
                                                                </span>
                                                            </span>
                                                        </label>
                                                        
                                                        @error('images')
                                                            <div class="alert alert-danger mt-3">{{ $message }}</div>
                                                        @enderror
                                                        @error('images.*')
                                                            <div class="alert alert-danger mt-3">{{ $message }}</div>
                                                        @enderror
                                                        
                                                        <!-- Preview area for new images -->
                                                        <div id="upload-preview-area" class="mt-25" style="display: none;">
                                                            <!-- Previews will be inserted here -->
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="alert alert-info">
                                                        <i class="uil uil-info-circle"></i> 
                                                        Достигнут лимит изображений (5 шт). Удалите ненужные, чтобы загрузить новые.
                                                    </div>
                                                @endif
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

@push('scripts')
<script>
$(document).ready(function() {
    const existingImagesCount = {{ $product->images->count() }};
    const maxFiles = 5;
    const maxFileSize = 5 * 1024 * 1024; // 5 MB
    let selectedFiles = [];
    
    // Удаление существующего изображения
    $(document).on('click', '.delete-existing-image', function() {
        const button = $(this);
        const imageId = button.data('image-id');
        const card = button.closest('.col-md-6[data-image-id="' + imageId + '"]');
        
        Swal.fire({
            title: 'Удалить изображение?',
            text: 'Это действие нельзя отменить',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Да, удалить',
            cancelButtonText: 'Отмена'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("account.store.products.images.destroy", ":id") }}'.replace(':id', imageId),
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        card.fadeOut(300, function() {
                            $(this).remove();
                            updateImageCount();
                        });
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Удалено',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Ошибка',
                            text: xhr.responseJSON?.message || 'Не удалось удалить изображение'
                        });
                    }
                });
            }
        });
    });
    
    // Сделать изображение главным
    $(document).on('click', '.set-main-image', function() {
        const button = $(this);
        const imageId = button.data('image-id');
        const currentCard = button.closest('[data-image-id]');
        
        // Показываем индикатор загрузки
        button.prop('disabled', true).html('<i class="uil uil-spinner-alt"></i> Обработка...');
        
        $.ajax({
            url: '{{ route("account.store.products.images.set-main", ":id") }}'.replace(':id', imageId),
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            success: function(response) {
                // Убираем бейдж "Главное" у всех изображений
                $('#existing-images-container .badge-primary').remove();
                
                // Показываем все кнопки "Сделать главным"
                $('#existing-images-container .set-main-image').each(function() {
                    $(this).show().prop('disabled', false).html('<i class="uil uil-star"></i> Сделать главным');
                });
                
                // Добавляем бейдж "Главное" в правильное место
                const targetDiv = currentCard.find('.card-body .d-flex.align-items-start .flex-grow-1 .d-flex.justify-content-between.align-items-start > div').first();
                targetDiv.prepend('<span class="btn btn-light btn-default btn-squared">Главное</span>');
                
                // Скрываем кнопку "Сделать главным" у текущего изображения
                button.hide();
                
                // Показываем уведомление
                Swal.fire({
                    icon: 'success',
                    title: 'Готово!',
                    text: 'Главное изображение обновлено',
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            },
            error: function(xhr) {
                // Восстанавливаем кнопку
                button.prop('disabled', false).html('<i class="uil uil-star"></i> Сделать главным');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: xhr.responseJSON?.message || 'Не удалось изменить главное изображение'
                });
            }
        });
    });
    
    // Обновление счетчика изображений
    function updateImageCount() {
        const count = $('#existing-images-container [data-image-id]').length;
        $('.badge-light').text(count + '/5');
        
        if (count < 5 && $('.new-images-upload').length === 0) {
            location.reload();
        }
    }
    
    // Загрузка новых изображений (если секция существует)
    if ($('#upload-images').length) {
        $('#upload-images').on('change', function(e) {
            handleFiles(e.target.files);
        });
        
        // Drag and drop
        $('.upload-product-img').on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragging');
        });
        
        $('.upload-product-img').on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragging');
        });
        
        $('.upload-product-img').on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragging');
            
            const files = e.originalEvent.dataTransfer.files;
            handleFiles(files);
        });
        
        function handleFiles(files) {
            const filesArray = Array.from(files);
            const currentTotal = existingImagesCount + selectedFiles.length;
            
            // Check total count
            if (currentTotal + filesArray.length > maxFiles) {
                alert(`Можно загрузить ещё ${maxFiles - currentTotal} изображений`);
                return;
            }
            
            filesArray.forEach((file, arrayIndex) => {
                // Validate file size
                if (file.size > maxFileSize) {
                    alert(`Файл ${file.name} слишком большой (макс. 5 МБ)`);
                    return;
                }
                
                // Validate file type
                if (!file.type.match('image.*')) {
                    alert(`Файл ${file.name} не является изображением`);
                    return;
                }
                
                // Вычисляем правильный индекс ДО добавления в массив
                const correctIndex = selectedFiles.length;
                selectedFiles.push(file);
                
                // Create preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    addPreview(file, e.target.result, correctIndex);
                };
                reader.readAsDataURL(file);
            });
            
            updateFileInput();
        }
        
        function addPreview(file, src, index) {
            const isMain = existingImagesCount === 0 && index === 0;
            const sizeKB = (file.size / 1024).toFixed(1);
            
            const previewHtml = `
                <div class="upload-product-media d-flex justify-content-between align-items-center mt-15" data-file-index="${index}">
                    <div class="upload-media-area d-flex">
                        <img src="${src}" alt="${file.name}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                        <div class="upload-media-area__title d-flex flex-wrap align-items-center ms-10">
                            <div>
                                <p class="mb-0">${file.name}</p>
                                <span class="fs-13 text-muted">${sizeKB} KB</span>
                                ${isMain ? '<span class="btn btn-light btn-default btn-squared ms-2">Главное</span>' : ''}
                            </div>
                        </div>
                    </div>
                    <div class="upload-media-area__btn">
                        <button type="button" 
                                class="btn-remove-image transparent rounded-circle wh-30 border-0 color-danger" 
                                data-file-index="${index}">
                            <i class="uil uil-trash-alt"></i>
                        </button>
                    </div>
                </div>
            `;
            
            $('#upload-preview-area').append(previewHtml).show();
        }
        
        // Remove new image preview
        $(document).on('click', '.btn-remove-image', function(e) {
            e.preventDefault();
            const card = $(this).closest('.upload-product-media[data-file-index]');
            const visualIndex = $('#upload-preview-area .upload-product-media[data-file-index]').index(card);
            
            selectedFiles.splice(visualIndex, 1);
            card.remove();
            
            // Update indices and main badge
            updatePreviews();
            
            if (selectedFiles.length === 0) {
                $('#upload-preview-area').hide();
            }
            
            updateFileInput();
        });
        
        function updatePreviews() {
            $('#upload-preview-area .upload-product-media[data-file-index]').each(function(index) {
                $(this).attr('data-file-index', index);
                $(this).find('.btn-remove-image').attr('data-file-index', index);
                
                // Remove all main badges first
                $(this).find('.badge-primary, .btn-squared:contains("Главное"), .btn-squared:contains("Главное")').remove();
                
                // Add main badge to first item only if no existing images
                if (existingImagesCount === 0 && index === 0) {
                    $(this).find('.upload-media-area__title > div').append(
                        '<span class="btn btn-light btn-default btn-squared ms-2">Главное</span>'
                    );
                }
            });
        }
        
        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            document.getElementById('upload-images').files = dataTransfer.files;
        }
    }
});
</script>

<style>
.upload-product-img.dragging {
    border-color: #5F63F2 !important;
    background-color: rgba(95, 99, 242, 0.05);
}

.upload-media-area img {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.existing-images .card {
    transition: all 0.3s ease;
}

.existing-images .card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>
@endpush