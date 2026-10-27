{{-- РЕДАКТИРОВАНИЕ ТОВАРА --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@php
    // Режим формы определяет, что произойдёт при сохранении и что показать продавцу заранее
    $moderationStatus = $product->moderation_status;
    $editMode = match (true) {
        $moderationStatus === \App\Enums\ProductModerationStatus::Approved => 'live',
        in_array($moderationStatus, [
            \App\Enums\ProductModerationStatus::Pending,
            \App\Enums\ProductModerationStatus::Processing,
            \App\Enums\ProductModerationStatus::NeedsReview,
            \App\Enums\ProductModerationStatus::Failed,
        ], true) => 'review',
        default => 'draft', // черновик, «нужно исправить», «отклонён»
    };
    $submitLabel = $editMode === 'draft' ? 'Отправить на проверку' : 'Сохранить изменения';
    $recheckFields = \App\Services\ProductStatusStoryFactory::RECHECK_FIELDS;
    $originalContent = [
        'name' => $product->name,
        'desc' => $product->desc,
        'features1' => $product->features1,
        'features2' => $product->features2,
        'features3' => $product->features3,
        'features4' => $product->features4,
        'category_id' => $product->category_id,
    ];
@endphp

<div class="contents product-form-page product-edit-page">
    <div class="container-fluid product-form-page__container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">Редактировать товар</h4>
                    <div class="breadcrumb-action justify-content-center flex-wrap gap-2">
                        <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">
                            <i class="uil uil-arrow-left"></i> Назад
                        </a>
                        <button type="submit"
                                form="product-edit-form"
                                name="save_action"
                                value="save_and_submit"
                                class="btn btn-sm btn-primary">
                            <i class="uil uil-shield-check"></i> {{ $submitLabel }}
                        </button>
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
                                <form action="{{ route('account.store.products.update', $product) }}" 
                                      method="POST" 
                                      enctype="multipart/form-data"
                                      id="product-edit-form"
                                      class="product-edit-form"
                                      data-recheck-mode="{{ $editMode }}"
                                      data-original="{{ json_encode($originalContent, JSON_UNESCAPED_UNICODE) }}"
                                      novalidate>
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden"
                                           name="expected_moderation_version"
                                           value="{{ $product->moderation_version }}">
                                    <x-product-moderation-status :product="$product" :issues="$moderationIssues" />

                                    @if($editMode === 'live')
                                        <div class="product-recheck-hint">
                                            <strong>Что можно менять без проверки.</strong>
                                            Цену, скидку и условия заказа: они применяются сразу.
                                            Если изменить {{ $recheckFields }}, товар скроется из каталога и пройдёт проверку заново.
                                        </div>
                                    @elseif($editMode === 'review')
                                        <div class="product-recheck-hint">
                                            <strong>Что можно менять свободно.</strong>
                                            Цену, скидку и условия заказа: проверка от этого не собьётся и изменения применятся сразу.
                                        </div>
                                    @endif

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

                                                {{-- ✅ СООБЩЕНИЕ ОБ ОШИБКЕ --}}

                                                {{-- ✅ ОШИБКИ ВАЛИДАЦИИ  --}}
                                                @if($errors->any())
                                                    <div class="alert-big alert alert-danger alert-dismissible fade show" role="alert">
                                                        <div class="alert-content">
                                                            <h6 class="alert-heading">Ошибка при сохранении товара</h6>
                                                            <ul class="mb-0">
                                                                @foreach($errors->all() as $error)
                                                                    <li>{{ $error }}</li>
                                                                @endforeach
                                                            </ul>
                                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
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
                                                           class="form-control product-form-name-field product-form-scroll-anchor @error('name') is-invalid @enderror"
                                                           id="name" 
                                                           name="name" 
                                                           rows="1"
                                                           placeholder="Введите название товара"
                                                           maxlength="70"
                                                           required>{{ old('name', $product->name) }}</textarea>
                                                    <div class="d-flex justify-content-between align-items-start mt-1">
                                                        <small class="text-muted">Рекомендуемая длина — 35 символов. Более длинные названия могут обрезаться в каталоге</small>
                                                        <small class="ms-2">
                                                            <span id="name-counter" class="fw-500">{{ strlen(old('name', $product->name)) }}</span>/70
                                                        </small>
                                                    </div>
                                                    @error('name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror

                                                    <div class="form-check form-switch mt-3">
                                                        <input type="hidden" name="can_order" value="0">
                                                        <input class="form-check-input" type="checkbox" name="can_order"
                                                               id="can_order" value="1" @checked(old('can_order', $product->can_order))>
                                                        <label class="form-check-label" for="can_order">Разрешить заказ товара через сайт</label>
                                                    </div>
                                                    <div class="form-check form-switch mt-2">
                                                        <input type="hidden" name="delivery_enabled" value="0">
                                                        <input class="form-check-input" type="checkbox" name="delivery_enabled"
                                                               id="delivery_enabled" value="1" @checked(old('delivery_enabled', $product->delivery_enabled))>
                                                        <label class="form-check-label" for="delivery_enabled">Разрешить доставку этого товара</label>
                                                    </div>
                                                </div>

                                                {{-- Категория --}}
                                                <div class="form-group">
                                                    <label>Категория <span class="text-danger">*</span></label>
                                                    <div id="category-selectors" class="product-form-scroll-anchor">
                                                        {{-- Будет заполнено через JS --}}
                                                    </div>
                                                    
                                                    <input type="hidden" name="category_id" id="category_id" value="{{ old('category_id', $product->category_id) }}">
                                                    
                                                    <small class="text-muted">Выберите категорию. Если есть подкатегории, появятся дополнительные списки.</small>
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

                                                {{-- VIP: покупается на странице товара --}}
                                                <div class="form-group mb-20">
                                                    <div class="alert alert-info d-flex align-items-start py-2 px-3 mb-0 product-form-callout product-form-callout--info">
                                                        <i class="uil uil-star me-2 mt-1 product-form-callout__icon text-warning"></i>
                                                        <div>
                                                            <small class="d-block mb-0">
                                                                @if($product->isVipActive())
                                                                    <strong>VIP действует{{ $product->vip_until ? ' до '.$product->vip_until->format('d.m.Y H:i') : ' без ограничения по сроку' }}.</strong>
                                                                @else
                                                                    <strong>VIP товары показываются первыми</strong> в каталоге и в поиске.
                                                                @endif
                                                                Подключить или продлить VIP можно на <a href="{{ route('account.store.products.show', $product) }}">странице товара</a>.
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Описание --}}
                                                <div class="form-group">
                                                    <label for="desc">Описание товара</label>
                                                    <textarea class="form-control product-form-autosize product-form-scroll-anchor @error('desc') is-invalid @enderror"
                                                              id="desc" 
                                                              name="desc" 
                                                              rows="4"
                                                              maxlength="500"
                                                              placeholder="Подробное описание товара">{{ old('desc', $product->desc) }}</textarea>
                                                    @error('desc')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Характеристики --}}
                                                <div class="form-group">
                                                    <label for="features1">Характеристика 1</label>
                                                    <textarea
                                                           class="form-control product-form-feature-field product-form-scroll-anchor @error('features1') is-invalid @enderror"
                                                           id="features1" 
                                                           name="features1" 
                                                           rows="1"
                                                           maxlength="30"
                                                           placeholder="Например: Цвет: Красный">{{ old('features1', $product->features1) }}</textarea>
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
                                                           placeholder="Например: Размер: L">{{ old('features2', $product->features2) }}</textarea>
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
                                                           maxlength="30"
                                                           rows="1"
                                                           placeholder="Например: Материал: Хлопок">{{ old('features3', $product->features3) }}</textarea>
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
                                                           placeholder="Например: Вес: 500г">{{ old('features4', $product->features4) }}</textarea>
                                                    @error('features4')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Изображения товара --}}
                                    <div class="card add-product product-form-section product-form-scroll-anchor mb-24"
                                         id="product-images-section">
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
                                                        @foreach($product->images->sortByDesc('is_main') as $image)
                                                            <div class="upload-product-media d-flex justify-content-between align-items-center mt-15" data-image-id="{{ $image->id }}">
                                                                <div class="upload-media-area d-flex">
                                                                    <img src="{{ asset('storage/' . $image->path) }}" 
                                                                         alt="Product image" 
                                                                        class="product-form-preview-thumb">
                                                                    <div class="upload-media-area__title d-flex flex-wrap align-items-center ms-10">
                                                                        <div>
                                                                            <p class="mb-0">{{ Str::limit(basename($image->path), 8, '...') }}</p>
                                                                            <span class="fs-13 text-muted">{{ number_format(Storage::disk('public')->size($image->path) / 1024, 1) }} KB</span>
                                                                            @if($image->is_main)
                                                                                <span class="btn btn-light btn-default btn-squared ms-2">Главное</span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="upload-media-area__btn d-flex gap-2">
                                                                    @if(!$image->is_main && $product->images->count() > 1)
                                                                        <button type="button"
                                                                                class="set-main-image product-form-preview-main-button"
                                                                                data-image-id="{{ $image->id }}"
                                                                                title="Сделать главным">
                                                                            <i class="uil uil-star"></i>
                                                                        </button>
                                                                    @endif
                                                                    <button type="button" 
                                                                            class="delete-existing-image transparent rounded-circle wh-30 border-0 color-danger" 
                                                                            data-image-id="{{ $image->id }}"
                                                                            title="Удалить">
                                                                        <i class="uil uil-trash-alt"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
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
                                                <div id="upload-preview-area" class="mt-25 product-form-preview-area">
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
                                    @if($editMode === 'draft')
                                        <p class="product-form-actions__hint">
                                            После отправки товар проверят. Пока проверка не пройдена, покупатели его не видят,
                                            а после одобрения он опубликуется сам. «Сохранить черновик» ничего никуда не отправляет.
                                        </p>
                                    @endif
                                    <div class="button-group add-product-btn product-form-actions d-flex justify-content-end gap-2">
                                        <a href="{{ route('account.store.products.index') }}" 
                                           class="btn btn-light btn-default btn-squared fw-400">
                                            Отмена
                                        </a>
                                        @if($editMode === 'draft')
                                            <button type="submit"
                                                    name="save_action"
                                                    value="save_draft"
                                                    class="btn btn-light btn-default btn-squared">
                                                <i class="uil uil-save"></i> Сохранить черновик
                                            </button>
                                        @endif
                                        <button type="submit"
                                                name="save_action"
                                                value="save_and_submit"
                                                class="btn btn-primary btn-default btn-squared">
                                            <i class="uil uil-shield-check"></i> {{ $submitLabel }}
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
document.querySelectorAll('.product-form-autosize').forEach((field) => {
    const resizeField = () => {
        field.style.height = 'auto';
        field.style.height = `${field.scrollHeight}px`;
    };

    field.addEventListener('input', resizeField);
    resizeField();
});

    // === ДИНАМИЧЕСКИЙ ВЫБОР КАТЕГОРИЙ ===
const categoryContainer = document.getElementById('category-selectors');
const categoryHiddenInput = document.getElementById('category_id');
const selectedPath = @json(session('selectedPath', $selectedPath ?? []));

// Инициализация с предзаполнением
initializeCategorySelectors();

async function initializeCategorySelectors() {
    // Загружаем корневые категории
    await loadCategoryLevel(null, 0, selectedPath[0] || null);
    
    // Загружаем остальные уровни
    for (let i = 0; i < selectedPath.length; i++) {
        if (i + 1 < selectedPath.length) {
            await loadCategoryLevel(selectedPath[i], i + 1, selectedPath[i + 1]);
        }
    }
}

async function loadCategoryLevel(parentId, level, selectedId = null) {
    const url = parentId 
        ? `{{ route('account.categories.children') }}?parent_id=${parentId}`
        : `{{ route('account.categories.children') }}`;
        
    const response = await fetch(url);
    const categories = await response.json();
    
    if (categories.length === 0) return;
    
    const select = createCategorySelect(level, categories, selectedId);
    categoryContainer.appendChild(select);
}

function createCategorySelect(level, categories, selectedId = null) {
    const select = document.createElement('select');
    select.className = 'form-control mb-2 category-selector';
    select.dataset.level = level;
    
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = level === 0 ? 'Выберите категорию' : 'Выберите подкатегорию';
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
    
    return select;
}

// Обработчик изменения
categoryContainer.addEventListener('change', function(e) {
    if (!e.target.classList.contains('category-selector')) return;
    
    const categoryId = e.target.value;
    const currentLevel = parseInt(e.target.dataset.level);
    
    // Удаляем все селекты после текущего
    const selectors = categoryContainer.querySelectorAll('.category-selector');
    selectors.forEach(selector => {
        if (parseInt(selector.dataset.level) > currentLevel) {
            selector.remove();
        }
    });
    
    if (!categoryId) {
        categoryHiddenInput.value = '';
        return;
    }
    
    categoryHiddenInput.value = categoryId;
    
    // Загружаем дочерние категории
    loadCategoryChildren(categoryId, currentLevel + 1);
});

function loadCategoryChildren(parentId, level) {
    fetch(`{{ route('account.categories.children') }}?parent_id=${parentId}`)
        .then(response => response.json())
        .then(children => {
            if (children.length === 0) return;
            
            const select = createCategorySelect(level, children);
            categoryContainer.appendChild(select);
        });
}
// Восстановление при ошибке валидации
const oldCategoryId = categoryHiddenInput.value;
const hasError = document.querySelector('#category_id').closest('.form-group').querySelector('.invalid-feedback');

if (hasError && oldCategoryId) {
    restoreCategorySelection(oldCategoryId);
}

async function restoreCategorySelection(categoryId) {
    // Находим первый выбранный селект (уже загружен через selectedPath)
    const firstSelect = categoryContainer.querySelector('[data-level="0"]');
    if (!firstSelect || !firstSelect.value) return;
    
    const selectedValue = firstSelect.value;
    
    // Загружаем дочерние категории и показываем пустой красный селект
    fetch(`{{ route('account.categories.children') }}?parent_id=${selectedValue}`)
        .then(response => response.json())
        .then(children => {
            if (children.length > 0) {
                // Создаем пустой красный селект
                const select = document.createElement('select');
                select.className = 'form-control mb-2 category-selector is-invalid';
                select.dataset.level = 1;
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
                
                categoryContainer.appendChild(select);
            }
        });
}

// === КОНЕЦ БЛОКА КАТЕГОРИЙ ===

// Подтверждение перед правкой, из-за которой опубликованный товар вернётся на проверку
(function () {
    const form = document.getElementById('product-edit-form');
    if (!form) return;

    const mode = form.dataset.recheckMode;
    if (mode !== 'live' && mode !== 'review') return;

    let original = {};
    try { original = JSON.parse(form.dataset.original || '{}'); } catch (e) { return; }

    const labels = {
        name: 'название', desc: 'описание', features1: 'характеристики', features2: 'характеристики',
        features3: 'характеристики', features4: 'характеристики', category_id: 'категория'
    };
    const normalize = (value) => String(value ?? '').replace(/\r\n/g, '\n').trim();
    let confirmed = false;

    form.addEventListener('submit', function (event) {
        if (confirmed) return;

        const changed = [];
        Object.keys(labels).forEach(function (field) {
            const element = form.elements[field];
            if (element && normalize(element.value) !== normalize(original[field]) && changed.indexOf(labels[field]) === -1) {
                changed.push(labels[field]);
            }
        });

        const fileInput = form.querySelector('input[type="file"][name="images[]"]');
        if (fileInput && fileInput.files && fileInput.files.length) changed.push('фото');

        if (!changed.length) return;

        event.preventDefault();
        const submitter = event.submitter || null;

        Swal.fire({
            title: mode === 'live' ? 'Товар пропадёт из каталога' : 'Проверка начнётся заново',
            html: '<p>Вы изменили: <strong>' + changed.join(', ') + '</strong>.</p>' +
                (mode === 'live'
                    ? '<p class="mb-0">Такие правки проверяются заново, поэтому товар скроется от покупателей, пока проверка не закончится. После одобрения он опубликуется сам.</p>'
                    : '<p class="mb-0">Текущая проверка будет отменена, товар снова встанет в очередь.</p>'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#5F63F2',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Да, сохранить и отправить на проверку',
            cancelButtonText: 'Вернуться к редактированию',
            reverseButtons: true
        }).then(function (result) {
            if (!result.isConfirmed) return;
            confirmed = true;
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter);
            } else {
                if (submitter && submitter.name) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = submitter.name;
                    hidden.value = submitter.value;
                    form.appendChild(hidden);
                }
                form.submit();
            }
        });
    });
})();
$(document).ready(function() {
    let existingImagesCount = {{ $product->images->count() }}; // ✅ let вместо const
    const maxFiles = 5;
    const maxFileSize = 5 * 1024 * 1024; // 5 MB
    let selectedFiles = [];
    
    // Удаление существующего изображения
    $(document).on('click', '.delete-existing-image', function() {
        const button = $(this);
        const imageId = button.data('image-id');
        const card = button.closest('.upload-product-media[data-image-id="' + imageId + '"]');
        
        Swal.fire({
            title: 'Удалить изображение?',
            text: ({
                live: 'Это действие нельзя отменить. Товар вернётся в черновики и пропадёт из каталога до новой проверки.',
                review: 'Это действие нельзя отменить. Текущая проверка отменится, товар вернётся в черновики.'
            })[$('#product-edit-form').data('recheck-mode')] || 'Это действие нельзя отменить',
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
                            existingImagesCount--; // ✅ УМЕНЬШАЕМ СЧЕТЧИК
                            updateImageCount();
                        });

                        if (response.current_version) {
                            $('input[name="expected_moderation_version"]').val(response.current_version);
                        }
                        
                        // Удаление фото могло вернуть товар в черновик: обновляем блок статуса и убираем устаревшие замечания
                        if (response.status_html) {
                            $('#product-status-box').replaceWith(response.status_html);
                        }
                        if (response.state_changed) {
                            $('#product-edit-form').attr('data-recheck-mode', 'draft');
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Удалено',
                            text: response.message,
                            timer: response.state_changed ? 6000 : 1500,
                            showConfirmButton: !!response.state_changed
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Ошибка',
                            text: xhr.responseJSON?.error || xhr.responseJSON?.message || 'Не удалось удалить изображение'
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
        const currentCard = button.closest('.upload-product-media[data-image-id]');
        
        // Показываем индикатор загрузки
        button.prop('disabled', true).html('<i class="uil uil-spinner-alt"></i>');
        
        $.ajax({
            url: '{{ route("account.store.products.images.set-main", ":id") }}'.replace(':id', imageId),
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            success: function(response) {
                // Убираем все бейджи "Главное" и показываем кнопки
                $('.existing-images .upload-product-media').each(function() {
                    $(this).find('.btn-squared:contains("Главное")').remove();
                    const setMainBtn = $(this).find('.set-main-image');
                    if (setMainBtn.length === 0 && $(this).data('image-id') !== imageId) {
                        $(this).find('.upload-media-area__btn').prepend(
                            `<button type="button"
                                    class="set-main-image product-form-preview-main-button"
                                    data-image-id="${$(this).data('image-id')}"
                                    title="Сделать главным">
                                <i class="uil uil-star"></i>
                            </button>`
                        );
                    }
                });
                
                // Добавляем бейдж "Главное" к текущему
                currentCard.find('.upload-media-area__title > div').append(
                    '<span class="btn btn-light btn-default btn-squared ms-2">Главное</span>'
                );
                
                // Убираем кнопку "Сделать главным" у текущего изображения
                button.remove();
                
            },
            error: function(xhr) {
                // Восстанавливаем кнопку
                button.prop('disabled', false).html('<i class="uil uil-star"></i>');
                
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
        const count = $('.existing-images .upload-product-media[data-image-id]').length;
        existingImagesCount = count; // ✅ СИНХРОНИЗИРУЕМ СЧЕТЧИК С DOM
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
            // ✅ Перепроверяем через DOM для надежности
            const currentExistingImages = $('.existing-images .upload-product-media[data-image-id]').length;
            existingImagesCount = currentExistingImages; // Синхронизируем
            const currentTotal = existingImagesCount + selectedFiles.length;
            
            // Check total count
            if (currentTotal + filesArray.length > maxFiles) {
                const available = maxFiles - currentTotal;
                Swal.fire({
                    icon: 'warning',
                    title: 'Превышен лимит',
                    text: `Можно загрузить ещё ${available} ${available === 1 ? 'изображение' : 'изображений'}`,
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
                        title: 'Файл слишком большой. Пожалуйста, выберите файл поменьше или уменьшите его.',
                        html: `<strong>${file.name}</strong><br>Максимальный размер: 5 МБ`,
                        confirmButtonColor: '#5F63F2'
                    });
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
            // ✅ Используем реальный счетчик из DOM
            const currentExistingImages = $('.existing-images .upload-product-media[data-image-id]').length;
            const isMain = currentExistingImages === 0 && index === 0;
            const sizeKB = (file.size / 1024).toFixed(1);
            
            const previewHtml = `
                <div class="upload-product-media d-flex justify-content-between align-items-center mt-15" data-file-index="${index}">
                    <div class="upload-media-area d-flex">
                        <img src="${src}" alt="${file.name}" class="product-form-preview-thumb">
                        <div class="upload-media-area__title d-flex flex-wrap align-items-center ms-10">
                            <div>
                                <p class="mb-0">${file.name.length > 8 ? file.name.substring(0, 8) + '...' : file.name}</p>
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
            // ✅ Проверяем реальное количество существующих изображений
            const currentExistingImages = $('.existing-images .upload-product-media[data-image-id]').length;
            
            $('#upload-preview-area .upload-product-media[data-file-index]').each(function(index) {
                $(this).attr('data-file-index', index);
                $(this).find('.btn-remove-image').attr('data-file-index', index);
                
                // Remove all main badges first
                $(this).find('.badge-primary, .btn-squared:contains("Главное")').remove();
                
                // Add main badge to first item only if no existing images
                if (currentExistingImages === 0 && index === 0) {
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
});
</script>
@endpush
