{{-- ДОБАВЛЕНИЕ ТОВАРА --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

<div class="contents product-form-page product-create-page">
    <div class="container-fluid product-form-page__container">
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

    <div class="container-fluid product-form-page__container">
        <div class="row">
            <div class="col-lg-12">
                <div class="product-add product-form-shell w-100">
                    <div class="row">
                        <div class="col-12 product-form-column">
                            <div class="product-form-inner">
                                <form action="{{ route('account.store.products.store') }}" 
                                      method="POST" 
                                      enctype="multipart/form-data"
                                      class="product-create-form"
                                      novalidate>
                                    @csrf

                                    <div class="product-form-guide">
                                        <div class="product-form-guide__main">
                                            <span class="product-form-guide__eyebrow">Новый товар</span>
                                            <h5>Заполните карточку так, чтобы покупатель сразу понял, где и что он покупает.</h5>
                                            <p>Минимум для публикации: название, категория, цена и фото. После отправки товар попадёт на проверку.</p>
                                        </div>
                                        <div class="product-form-guide__steps" aria-label="Этапы публикации товара">
                                            <div class="product-form-guide__step">
                                                <span>1</span>
                                                <strong>Данные</strong>
                                                <small>Название, цена, категория</small>
                                            </div>
                                            <div class="product-form-guide__step">
                                                <span>2</span>
                                                <strong>Фото</strong>
                                                <small>До 5 изображений</small>
                                            </div>
                                            <div class="product-form-guide__step">
                                                <span>3</span>
                                                <strong>Проверка</strong>
                                                <small>Отправка на модерацию</small>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Основная информация --}}
                                    <div class="card add-product product-form-section mb-24">
                                        <div class="card-body p-0">
                                            <div class="card-header">
                                                <h6 class="fw-500">Информация о товаре</h6>
                                            </div>

                                            {{-- ============================================ --}}
                                            {{-- БЛОК СООБЩЕНИЙ И ОШИБОК --}}
                                            {{-- ============================================ --}}
                                            <div class="product-form-alerts pt-20">
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
                                                    <textarea
                                                           class="form-control product-form-name-field @error('name') is-invalid @enderror"
                                                           id="name" 
                                                           name="name" 
                                                           rows="1"
                                                           placeholder="Введите название товара"
                                                           maxlength="70"
                                                           required>{{ old('name') }}</textarea>
                                                    <div class="d-flex justify-content-between align-items-start mt-1">
                                                        <small class="text-muted">Рекомендуемая длина — 35 символов. Более длинные названия могут обрезаться в каталоге</small>
                                                        <small class="ms-2">
                                                            <span id="name-counter" class="fw-500">0</span>/70
                                                        </small>
                                                    </div>
                                                    @error('name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror

                                                    <div class="form-check form-switch mt-3">
                                                        <input type="hidden" name="can_order" value="0">
                                                        <input class="form-check-input" type="checkbox" name="can_order"
                                                               id="can_order" value="1" @checked(old('can_order', true))>
                                                        <label class="form-check-label" for="can_order">Разрешить заказ товара через сайт</label>
                                                    </div>
                                                    <div class="form-check form-switch mt-2">
                                                        <input type="hidden" name="delivery_enabled" value="0">
                                                        <input class="form-check-input" type="checkbox" name="delivery_enabled"
                                                               id="delivery_enabled" value="1" @checked(old('delivery_enabled', true))>
                                                        <label class="form-check-label" for="delivery_enabled">Разрешить доставку этого товара</label>
                                                    </div>
                                                </div>

                                                {{-- Категория --}}
                                                <div class="form-group">
                                                    <label>Категория <span class="text-danger">*</span></label>
                                                    <div id="category-selectors">
                                                        {{-- Первый уровень --}}
                                                        <select class="form-control mb-2 category-selector @error('category_id') is-invalid @enderror" 
                                                                data-level="0" 
                                                                required>
                                                            <option value="">Выберите категорию</option>
                                                            @foreach($categories as $cat)
                                                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                                                    {{ $cat->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    
                                                    {{-- Скрытое поле для отправки финального ID --}}
                                                    <input type="hidden" name="category_id" id="category_id" value="{{ old('category_id') }}">
                                                    
                                                    <small class="text-muted">Выберите категорию. Если вашей категории товара нет, обратиться к администратору.</small>
                                                    @error('category_id')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Цена --}}
                                                <div class="form-group">
                                                    <label>Цена <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <input type="number" 
                                                               class="form-control @error('price') is-invalid @enderror" 
                                                               name="price" 
                                                               value="{{ old('price') }}" 
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

                                                {{-- ✅ VIP ТОВАР С ИНФОРМАЦИОННЫМ БЛОКОМ --}}
                                                <div class="form-group mb-20">
                                                    @php($vipAllowed = $vipAvailability['allowed'] ?? true)

                                                    @if($vipAllowed)
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

                                                        {{-- ℹ️ Информационное сообщение --}}
                                                        <div class="alert alert-info d-flex align-items-start py-2 px-3 mt-2 mb-0 product-form-callout product-form-callout--info">
                                                            <i class="uil uil-info-circle me-2 mt-1 product-form-callout__icon text-primary"></i>
                                                            <div>
                                                                <small class="d-block mb-0">
                                                                    <strong>VIP товары показываются всегда первыми</strong> в каталоге и в поиске
                                                                </small>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="alert alert-warning d-flex align-items-start py-2 px-3 mt-2 mb-0 product-form-callout product-form-callout--warning">
                                                            <i class="uil uil-exclamation-triangle me-2 mt-1 product-form-callout__icon text-warning"></i>
                                                            <div>
                                                                <small class="d-block mb-0 text-dark">
                                                                    <strong>{{ $vipAvailability['message'] }}</strong>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- ⚠️ Ошибка лимита VIP товаров --}}
                                                    @error('is_vip')
                                                        <div class="alert alert-warning d-flex align-items-start py-2 px-3 mt-2 mb-0 product-form-callout product-form-callout--warning">
                                                            <i class="uil uil-exclamation-triangle me-2 mt-1 product-form-callout__icon text-warning"></i>
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
                                                    <textarea class="form-control product-form-autosize @error('desc') is-invalid @enderror"
                                                              id="desc" 
                                                              name="desc" 
                                                              rows="4"
                                                              maxlength="500"
                                                              placeholder="Подробное описание товара">{{ old('desc') }}</textarea>
                                                    @error('desc')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Характеристики --}}
                                                <div class="form-group">
                                                    <label for="features1">Характеристика 1</label>
                                                    <textarea
                                                           class="form-control product-form-feature-field @error('features1') is-invalid @enderror"
                                                           id="features1" 
                                                           name="features1" 
                                                           rows="1"
                                                           maxlength="30"
                                                           placeholder="Например: Цвет: Красный">{{ old('features1') }}</textarea>
                                                    @error('features1')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="features2">Характеристика 2</label>
                                                    <textarea
                                                           class="form-control product-form-feature-field @error('features2') is-invalid @enderror"
                                                           id="features2" 
                                                           name="features2" 
                                                           rows="1"
                                                           maxlength="30"
                                                           placeholder="Например: Размер: L">{{ old('features2') }}</textarea>
                                                    @error('features2')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="features3">Характеристика 3</label>
                                                    <textarea
                                                           class="form-control product-form-feature-field @error('features3') is-invalid @enderror"
                                                           id="features3" 
                                                           name="features3" 
                                                           rows="1"
                                                           maxlength="30"
                                                           placeholder="Например: Материал: Хлопок">{{ old('features3') }}</textarea>
                                                    @error('features3')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group mb-0">
                                                    <label for="features4">Характеристика 4</label>
                                                    <textarea
                                                           class="form-control product-form-feature-field @error('features4') is-invalid @enderror"
                                                           id="features4" 
                                                           name="features4" 
                                                           rows="1"
                                                           maxlength="30"
                                                           placeholder="Например: Вес: 500г">{{ old('features4') }}</textarea>
                                                    @error('features4')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Изображения товара --}}
                                    <div class="card add-product product-form-section mb-24">
                                        <div class="card-body p-0">
                                            <div class="card-header">
                                                <h6 class="fw-500">Изображения товара</h6>
                                            </div>
                                            <!-- Start: product body -->
                                            <div class="add-product__body-img px-sm-40 px-20">
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
                                                        <span class="d-block mt-10 fs-13 text-muted">(JPG, PNG, WebP • макс. 5 МБ каждое • до 5 фото)</span>
                                                    </span>
                                                </label>
                                                
                                                @error('images')
                                                    <div class="alert alert-danger mt-3">{{ $message }}</div>
                                                @enderror
                                                @error('images.*')
                                                    <div class="alert alert-danger mt-3">{{ $message }}</div>
                                                @enderror
                                                
                                                <!-- Preview area -->
                                                <div id="upload-preview-area" class="mt-25 product-form-preview-area">
                                                    <!-- Previews will be inserted here -->
                                                </div>
                                            </div>
                                            <!-- End: product body -->
                                        </div>
                                    </div>

                                    {{-- Кнопки --}}
                                    <div class="button-group add-product-btn product-form-actions d-flex justify-content-end gap-2">
                                        <a href="{{ route('account.store.products.index') }}" 
                                           class="btn btn-light btn-default btn-squared fw-400 text-capitalize">
                                            Отмена
                                        </a>
                                        <button type="submit"
                                                name="save_action"
                                                value="save_draft"
                                                class="btn btn-light btn-default btn-squared text-capitalize">
                                            <i class="uil uil-save"></i> Сохранить черновик
                                        </button>
                                        <button type="submit"
                                                name="save_action"
                                                value="save_and_submit"
                                                class="btn btn-primary btn-default btn-squared text-capitalize">
                                            <i class="uil uil-shield-check"></i> Опубликовать
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
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.product-form-autosize').forEach((field) => {
        const resizeField = () => {
            field.style.height = 'auto';
            field.style.height = `${field.scrollHeight}px`;
        };

        field.addEventListener('input', resizeField);
        resizeField();
    });

    const container = document.getElementById('category-selectors');
    const hiddenInput = document.getElementById('category_id');
    const selectedPath = @json(session('selectedPath', []));
    
    // Обработчик изменения селекта
    container.addEventListener('change', function(e) {
        if (!e.target.classList.contains('category-selector')) return;
        
        const categoryId = e.target.value;
        const currentLevel = parseInt(e.target.dataset.level);
        
        // Удаляем все селекты после текущего
        const selectors = container.querySelectorAll('.category-selector');
        selectors.forEach((selector) => {
            if (parseInt(selector.dataset.level) > currentLevel) {
                selector.remove();
            }
        });
        
        if (!categoryId) {
            hiddenInput.value = '';
            return;
        }
        
        // Устанавливаем выбранную категорию
        hiddenInput.value = categoryId;
        
        // Загружаем дочерние категории
        loadChildren(categoryId, currentLevel + 1);
    });
    
    function loadChildren(parentId, level) {
        fetch(`{{ route('account.categories.children') }}?parent_id=${parentId}`)
            .then(response => response.json())
            .then(children => {
                if (children.length === 0) {
                    // Нет детей - это конечная категория
                    return;
                }
                
                // Создаем новый селект
                const select = document.createElement('select');
                select.className = 'form-control mb-2 category-selector';
                select.dataset.level = level;
                
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Выберите подкатегорию';
                select.appendChild(defaultOption);
                
                children.forEach(child => {
                    const option = document.createElement('option');
                    option.value = child.id;
                    option.textContent = child.name;
                    select.appendChild(option);
                });
                
                container.appendChild(select);
            })
            .catch(error => {
                console.error('Ошибка загрузки категорий:', error);
            });
    }
    
    // Восстановление при ошибке валидации
    if (selectedPath.length > 0) {
        restoreSelection();
    }

    async function restoreSelection() {
        // Загружаем первый уровень с выбором
        const firstSelect = container.querySelector('[data-level="0"]');
        if (firstSelect && selectedPath[0]) {
            firstSelect.value = selectedPath[0];
            hiddenInput.value = selectedPath[selectedPath.length - 1]; // Последний элемент - это конечная категория
            
            // Загружаем остальные уровни по цепочке
            for (let i = 0; i < selectedPath.length - 1; i++) {
                await loadCategoryLevel(selectedPath[i], i + 1, selectedPath[i + 1]);
            }
            
            // Если последний уровень не конечный - показываем пустой красный селект
            const hasError = document.querySelector('#category_id').closest('.form-group').querySelector('.invalid-feedback');
            if (hasError) {
                // Загружаем детей последней выбранной категории и показываем красный пустой селект
                const lastCategoryId = selectedPath[selectedPath.length - 1];
                const response = await fetch(`{{ route('account.categories.children') }}?parent_id=${lastCategoryId}`);
                const children = await response.json();
                
                if (children.length > 0) {
                    const select = document.createElement('select');
                    select.className = 'form-control mb-2 category-selector is-invalid';
                    select.dataset.level = selectedPath.length;
                    select.required = true;
                    
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'Выберите подкатегорию';
                    defaultOption.selected = true;
                    select.appendChild(defaultOption);
                    
                    children.forEach(child => {
                        const option = document.createElement('option');
                        option.value = child.id;
                        option.textContent = child.name;
                        select.appendChild(option);
                    });
                    
                    container.appendChild(select);
                }
            }
        }
    }

    async function loadCategoryLevel(parentId, level, selectedId) {
        const response = await fetch(`{{ route('account.categories.children') }}?parent_id=${parentId}`);
        const categories = await response.json();
        
        if (categories.length === 0) return;
        
        const select = document.createElement('select');
        select.className = 'form-control mb-2 category-selector';
        select.dataset.level = level;
        
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Выберите подкатегорию';
        select.appendChild(defaultOption);
        
        categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;
            if (cat.id == selectedId) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        
        container.appendChild(select);
    }
});

$(document).ready(function() {
    let selectedFiles = [];
    const maxFiles = 5;
    const maxFileSize = 5 * 1024 * 1024; // 5 MB
    
    // Handle file selection
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
        
        // Check total count
        if (selectedFiles.length + filesArray.length > maxFiles) {
            Swal.fire({
            icon: 'warning',
            title: 'Превышен лимит',
            text: `Можно загрузить максимум ${maxFiles} изображений`,
            confirmButtonText: 'Понятно',
            confirmButtonColor: '#5F63F2'
        });
            return;
        }
        
        filesArray.forEach((file, arrayIndex) => {
            // Validate file size
            if (file.size > maxFileSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'Файл слишком большой',
                    html: `<strong>${file.name}</strong><br>Максимальный размер: 5 МБ`,
                    confirmButtonColor: '#5F63F2'
                });
                return;
            }
            
            // Validate file type
            if (!file.type.match('image.*')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Неверный тип файла',
                    html: `<strong>${file.name}</strong><br>Поддерживаются только изображения (JPG, PNG, WebP)`,
                    confirmButtonColor: '#5F63F2'
                });
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
        const isMain = index === 0;
        const sizeKB = (file.size / 1024).toFixed(1);
        
        const previewHtml = `
            <div class="upload-product-media d-flex justify-content-between align-items-center mt-15" data-file-index="${index}">
                <div class="upload-media-area d-flex">
                    <img src="${src}" alt="${file.name}" class="product-form-preview-thumb">
                    <div class="upload-media-area__title d-flex flex-wrap align-items-center ms-10">
                        <div>
                            <p class="mb-0">${file.name.length > 8 ? file.name.substring(0, 8) + '...' : file.name}</p>
                            <span class="fs-13 text-muted">${sizeKB} KB</span>
                                ${isMain ? '<span class="btn btn-light btn-default btn-squared product-form-preview-main-badge">Главное</span>' : ''}
                        </div>
                    </div>
                </div>
                <div class="upload-media-area__btn">
                    <button type="button" 
                            class="btn-remove-image transparent rounded-circle wh-30 border-0 color-danger" 
                            data-file-index="${index}">
                        <img class="svg" src="{{ asset('cabinet/img/svg/trash-2.svg') }}" alt="">
                    </button>
                </div>
            </div>
        `;
        
        $('#upload-preview-area').append(previewHtml).show();
    }
    
    // Remove image
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
            
            // Удаляем ВСЕ бейджи "Главное" (по классу и тексту)
            $(this).find('.btn-squared:contains("Главное")').remove();
            $(this).find('.badge-primary').remove();
            
            // Add main badge to first item
            if (index === 0) {
                $(this).find('.upload-media-area__title > div').append(
                    '<span class="btn btn-light btn-default btn-squared">Главное</span>'
                );
            }
        });
    }
    
    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        document.getElementById('upload-images').files = dataTransfer.files;
    }
});

// Счетчик символов для названия товара
$('#name').on('input', function() {
    const length = $(this).val().length;
    const counter = $('#name-counter');
    
    counter.text(length);
    
    // Меняем цвет на красный при превышении 35 символов
    if (length >= 70) {
        counter.addClass('text-danger').removeClass('text-success');
    } else {
        counter.addClass('text-success').removeClass('text-danger');
    }
});

// Инициализация цвета счетчика при загрузке страницы
$(document).ready(function() {
    const initialLength = $('#name').val().length;
    const counter = $('#name-counter');
    
    if (initialLength >= 70) {
        counter.addClass('text-danger');
    } else if (initialLength > 0) {
        counter.addClass('text-success');
    }
});
</script>
@endpush
