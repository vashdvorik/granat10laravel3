{{-- КАТАЛОГ ТОВАРОВ --}}

@extends('backend.cabinet.layout_cabinet')
@inject('sellerPackageStatusService', 'App\Services\SellerPackageStatusService')

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
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-main">
                        <h4 class="breadcrumb-title">Каталог товаров</h4>
                        <div class="breadcrumb-action justify-content-center flex-wrap">
                            <a href="{{ route('account.store.products.create') }}" class="btn btn-sm btn-primary">
                                <i class="uil uil-plus"></i> Добавить товар
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $currentStatusKey = $currentProductStatus ?? 'all';
                $currentStatusFilter = collect($productStatusFilters ?? [])->firstWhere('key', $currentStatusKey);
                $allProductsCount = (int) (collect($productStatusFilters ?? [])->firstWhere('key', 'all')['count'] ?? $seller->products()->count());
                $hasAnyProducts = $allProductsCount > 0;
                $resetFilterQuery = request()->except('page', 'product_status', 'category');
            @endphp

            <div class="products_page product_page--grid seller-products-page mb-30">
                <div class="container-fluid">
                        @isset($sellerCabinetState)
                            @include('backend.cabinet.partials.seller-state-card', [
                                'sellerCabinetState' => $sellerCabinetState,
                                'seller' => $seller,
                            ])
                        @endisset

                        {{-- Уведомление если товары скрыты от покупателей --}}
                        @php
                            $packageStatus = $sellerPackageStatusService->getStatus($seller);
                        @endphp
                        @if(!$packageStatus['has_package'])
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="alert-big alert alert-danger" role="alert">
                                        <div class="alert-content">
                                            <h6 class="alert-heading">Ваши товары скрыты от покупателей</h6>
                                            <p>У вас нет активного пакета. Все товары недоступны для просмотра на сайте. 
                                            <a href="{{ route('account.packages.index') }}" class="alert-link">Выберите пакет</a>, чтобы ваши товары снова стали видны покупателям.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    <div class="seller-products-layout">

                        {{-- БОКОВАЯ ПАНЕЛЬ С КАТЕГОРИЯМИ --}}
                        <aside class="seller-products-filters">
                            <div class="widget mb-lg-30 mt-25">  {{-- ДОБАВИЛ mt-25 --}}
                                <div class="widget-header-title px-20 py-15">
                                    <h6 class="d-flex align-content-center fw-500">
                                        <img src="{{ asset('cabinet/img/svg/sliders.svg') }}" alt="sliders" class="svg"> Фильтр
                                    </h6>
                                </div>
                                <div class="category_sidebar">
                                    @if(isset($productStatusFilters))
                                        <aside class="product-sidebar-widget mb-30">
                                            <div class="widget_title" data-bs-toggle="collapse" data-bs-target="#productStatusFilters" role="button" aria-expanded="true">
                                                <h6>Статус товаров</h6>
                                            </div>
                                            <div class="card border-0 shadow-none multi-collapse mt-10 collapse show" id="productStatusFilters">
                                                <div class="product-category">
                                                    <ul class="category-list">
                                                        @foreach($productStatusFilters as $statusFilter)
                                                            @php
                                                                $statusQuery = request()->except('page');

                                                                if($statusFilter['key'] === 'all') {
                                                                    unset($statusQuery['product_status']);
                                                                } else {
                                                                    $statusQuery['product_status'] = $statusFilter['key'];
                                                                }
                                                            @endphp
                                                            <li>
                                                                <a href="{{ route('account.store.products.index', $statusQuery) }}"
                                                                   class="d-flex justify-content-between align-items-center category-link {{ ($currentProductStatus ?? 'all') === $statusFilter['key'] ? 'active' : '' }}">
                                                                    <span class="category-name">{{ $statusFilter['label'] }}</span>
                                                                    <span class="item-numbers">{{ $statusFilter['count'] }}</span>
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </aside>
                                    @endif

                                    <aside class="product-sidebar-widget mb-30">
                                        <div class="widget_title" data-bs-toggle="collapse" data-bs-target="#multiCollapseExample2" role="button" aria-expanded="true">
                                            <h6>Категории</h6>
                                        </div>
                                        <div class="card border-0 shadow-none multi-collapse mt-10 collapse show" id="multiCollapseExample2">
                                            <div class="product-category">
                                                <ul class="category-list">
                                                    {{-- Показать все товары --}}
                                                    <li>
                                                        <a href="{{ route('account.store.products.index', request()->except('page', 'category')) }}"
                                                           class="d-flex justify-content-between align-items-center category-link {{ !request('category') ? 'active' : '' }}">
                                                            <span class="category-name">
                                                                <i class="uil uil-apps"></i> Все товары
                                                            </span>
                                                            <span class="item-numbers">{{ $seller->products()->count() }}</span>
                                                        </a>
                                                    </li>
                                                    
                                                    @if(isset($categoryTree) && $categoryTree->count() > 0)
                                                        <li class="separator"></li>
                                                        @include('components.category-filter-tree', [
                                                            'items' => $categoryTree,
                                                            'level' => 1,
                                                            'seller' => $seller,
                                                            'sellerCategoryIds' => $sellerCategoryIds
                                                        ])
                                                    @else
                                                        <li class="text-muted text-center py-3">Нет товаров</li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </aside>
                                </div>
                            </div>
                        </aside>

                        {{-- ОСНОВНАЯ ОБЛАСТЬ С ТОВАРАМИ --}}
                        <main class="seller-products-main">
                            <div class="tab-content mt-25" id="ap-tabContent">
                                <div class="tab-pane fade show active" id="ap-overview" role="tabpanel" aria-labelledby="ap-overview-tab">
                                    

                                    @if($products->count() > 0)
                                        {{-- КАРТОЧКИ ТОВАРОВ --}}
                                        <div class="product-page-list">
                                            @foreach($products as $product)
                                                @php
                                                    $productCabinetState = $product->cabinet_state;
                                                    $nextStepUrl = match ($productCabinetState->primaryAction) {
                                                        'add_images', 'remove_vip' => route('account.store.products.edit', $product->id),
                                                        'choose_package', 'choose_visible_products' => route('account.packages.index'),
                                                        default => null,
                                                    };
                                                @endphp
                                                <div class="seller-product-card-col">
                                                    <div class="card product product--grid position-relative seller-product-card">
                                                        <div
                                                            class="position-absolute seller-product-card__menu"
                                                        >
                                                            <x-product-moderation-actions :product="$product" compact />
                                                        </div>
                                                        <div class="h-100">
                                                            <div class="product-item">
                                                                {{-- ИЗОБРАЖЕНИЕ ТОВАРА --}}
                                                                <div class="product-item__image seller-product-card__media">
                                                                    {{-- Статусы: VIP и Активность --}}
                                                                    <div class="position-absolute top-0 start-0 m-2 d-flex gap-2 seller-product-card__badges">
                                                                        @if($product->is_vip)
                                                                            <span class="seller-product-card__badge seller-product-card__badge--vip">
                                                                                <i class="uil uil-star"></i> VIP
                                                                            </span>
                                                                        @endif

                                                                        @if($productCabinetState->visibleToBuyers)
                                                                            <span class="seller-product-card__badge seller-product-card__badge--success">
                                                                                <i class="uil uil-check"></i> Виден
                                                                            </span>
                                                                        @else
                                                                            <span class="seller-product-card__badge seller-product-card__badge--muted">
                                                                                <i class="uil uil-pause"></i> {{ $productCabinetState->label }}
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    
                                                                    <a href="{{ route('account.store.products.show', $product->id) }}">
                                                                        @if($product->images->where('is_main', true)->first())
                                                                            <img class="card-img-top img-fluid" 
                                                                                 src="{{ asset('storage/' . $product->images->where('is_main', true)->first()->path) }}" 
                                                                                 alt="{{ $product->name }}">
                                                                        @elseif($product->images->first())
                                                                            <img class="card-img-top img-fluid" 
                                                                                 src="{{ asset('storage/' . $product->images->first()->path) }}" 
                                                                                 alt="{{ $product->name }}">
                                                                        @else
                                                                            <div class="no-image-placeholder">
                                                                                <i class="uil uil-image-slash"></i>
                                                                                <p>Нет фото</p>
                                                                            </div>
                                                                        @endif
                                                                    </a>
                                                                </div>

                                                                {{-- ИНФОРМАЦИЯ О ТОВАРЕ --}}
                                                                <div class="card-body px-20 pb-25 pt-25 seller-product-card__body">
                                                                    <div class="product-item__body">
                                                                        <a href="{{ route('account.store.products.show', $product->id) }}">
                                                                            <h6 class="card-title seller-product-card__title">{{ $product->name }}</h6>
                                                                        </a>
                                                                        
                                                                        {{-- Категория --}}
                                                                        @if($product->category)
                                                                            <small class="text-muted d-block mb-2 seller-product-card__meta">
                                                                                <i class="uil uil-tag-alt"></i> {{ $product->category->name }}
                                                                            </small>
                                                                        @endif

                                                                        <small class="text-muted d-block mb-2 seller-product-card__legacy-state">
                                                                            <i class="uil uil-shield-check"></i>
                                                                            <strong>Модерация:</strong>
                                                                            {{ $product->view_state->moderationLabel }}
                                                                            <br>
                                                                            <strong>Покупателям:</strong>
                                                                            {{ $productCabinetState->label }}
                                                                        </small>
                                                                        <small class="text-muted d-block mb-2 seller-product-card__legacy-state">
                                                                            {{ \Illuminate\Support\Str::limit($productCabinetState->description, 100) }}
                                                                        </small>
                                                                        <div class="seller-product-card__state seller-product-card__state--{{ $productCabinetState->color }}">
                                                                            <div class="seller-product-card__state-title">
                                                                                <i class="uil uil-shield-check"></i>
                                                                                {{ $productCabinetState->label }}
                                                                            </div>
                                                                            <div class="seller-product-card__state-text">
                                                                                {{ \Illuminate\Support\Str::limit($productCabinetState->description, 120) }}
                                                                            </div>
                                                                        </div>

                                                                        {{-- Дата последнего поднятия --}}
                                                                        @if($product->push_at)
                                                                            <small class="text-muted d-block mb-2">
                                                                                <i class="uil uil-clock"></i> Поднятие: {{ $product->push_at->format('d.m.Y в H:i') }}
                                                                            </small>
                                                                        @endif

                                                                        {{-- ЦЕНА --}}
                                                                        <div class="product-item__footer mb-3">
                                                                            <div class="d-flex align-items-center flex-wrap">
                                                                                @if($product->lowprice && $product->lowprice > 0)
                                                                                    {{-- Старая цена ЗАЧЕРКНУТАЯ (обычная цена) --}}
                                                                                    <span class="product-desc-price">{{ number_format($product->lowprice, 0) }} ₽</span>
                                                                                    {{-- Новая АКЦИОННАЯ цена (красная, жирная) --}}
                                                                                    <span class="product-price">{{ number_format($product->price, 0) }} ₽</span>
                                                                                    {{-- Процент скидки --}}
                                                                                    <span class="product-discount">
                                                                                        -{{ round((($product->price - $product->lowprice) / $product->price) * 100) }}%
                                                                                    </span>
                                                                                @else
                                                                                    {{-- Обычная цена БЕЗ зачеркивания --}}
                                                                                    <span class="product-desc-price">{{ number_format($product->price, 0) }} ₽</span>
                                                                                @endif
                                                                            </div>
                                                                        </div>

                                                                        <div class="d-flex mt-20 flex-wrap gap-2 seller-product-card__actions">
                                                                            <div class="flex-fill">
                                                                                <x-product-moderation-actions :product="$product" />
                                                                            </div>

                                                                            @if($product->view_state->primaryAction !== \App\Enums\ProductPrimaryAction::Edit)
                                                                            <a href="{{ route('account.store.products.edit', $product->id) }}" 
                                                                               class="btn btn-primary btn-default btn-squared border-0 flex-fill">
                                                                                Изменить
                                                                            </a>
                                                                            @endif
                                                                        </div>

                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- ПАГИНАЦИЯ --}}
                                        @if($products->hasPages())
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-center mt-4 mb-30">
                                                        {{ $products->links() }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        {{-- ПУСТОЕ СОСТОЯНИЕ (когда нет товаров) --}}
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="card seller-products-empty">
                                                    <div class="card-body text-center py-5">
                                                        @if($hasAnyProducts)
                                                            <div class="seller-products-empty__icon">
                                                                <i class="uil uil-filter-slash"></i>
                                                            </div>
                                                            <h5>По выбранным фильтрам товаров нет</h5>
                                                            <p>Сбросьте фильтры или выберите другой статус, чтобы увидеть остальные товары.</p>
                                                            <div class="seller-products-empty__actions">
                                                                <a href="{{ route('account.store.products.index', $resetFilterQuery) }}" class="btn btn-primary">
                                                                    Сбросить фильтры
                                                                </a>
                                                                <a href="{{ route('account.store.products.create') }}" class="btn btn-light">
                                                                    Добавить товар
                                                                </a>
                                                            </div>
                                                        @else
                                                        <i class="uil uil-box fs-40 text-muted mb-3 d-block"></i>
                                                        <h5 class="text-muted mb-3">У вас пока нет товаров</h5>
                                                        <p class="text-muted mb-4">Добавьте первый товар, чтобы начать продажи</p>
                                                        <a href="{{ route('account.store.products.create') }}" 
                                                           class="btn btn-primary">
                                                            <i class="uil uil-plus"></i> Добавить первый товар
                                                        </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </main>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ДОПОЛНИТЕЛЬНЫЕ СТИЛИ --}}
<style>
/*
/* Placeholder для товаров без изображения */
/*
.no-image-placeholder {
    width: 100%;
    aspect-ratio: 1 / 1;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
}
.no-image-placeholder i {
    font-size: 48px;
    margin-bottom: 10px;
}

/* Отступы между кнопками */
/*
.product-item__button.gap-2 {
    gap: 0.5rem;
}
.product-category .category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.product-category .category-list li {
    margin-bottom: 5px;
}

.product-category .category-list .separator {
    border-bottom: 1px solid #e3e6ef;
    margin: 10px 0;
}

.product-category .subcategories {
    list-style: none;
    padding: 0;
    margin: 5px 0 0 0;
}

.product-category .category-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 10px;
    color: #525768;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.product-category .category-link:hover {
    background: rgba(95, 99, 242, 0.05);
    color: #5F63F2;
}

.product-category .category-link.active {
    background: rgba(95, 99, 242, 0.1);
    color: #5F63F2;
    font-weight: 600;
}

.product-category .category-parent {
    display: block;
    padding: 5px 10px;
    color: #8c90a4;
    font-weight: 500;
    font-size: 14px;
}

.product-category .category-name {
    font-size: 14px;
}

.product-category .item-numbers {
    background: #f4f5f7;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    min-width: 25px;
    text-align: center;
}

.product-category .category-link.active .item-numbers {
    background: #5F63F2;
    color: white;
}

.product-category .category-link:hover .item-numbers {
    background: #5F63F2;
    color: white;
}
*/
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if (window.matchMedia('(max-width: 767.98px)').matches) {
        $('.seller-products-filters .multi-collapse.show').removeClass('show');
        $('.seller-products-filters .widget_title[aria-expanded="true"]').attr('aria-expanded', 'false');
    }

    // Настройка CSRF токена для всех AJAX запросов
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').first().val()
        }
    });
    
    // Подтверждение удаления товара
    $('.delete-product-btn').on('click', function(e) {
        e.preventDefault();
        const form = $(this).closest('.delete-product-form');
        const productName = form.data('product-name');

        Swal.fire({
            title: 'Удалить товар?',
            html: `
                <p>Вы уверены, что хотите удалить товар?</p>
                <h5 class="mt-2 mb-2 text-danger">${productName}</h5>
                <p class="text-muted mb-0">Это действие нельзя отменить</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="uil uil-trash-alt me-1"></i> Да, удалить',
            cancelButtonText: 'Отмена',
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Показываем индикатор загрузки
                Swal.fire({
                    title: 'Удаление...',
                    text: 'Пожалуйста, подождите',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Отправляем форму
                form.submit();
            }
        });
    });

    // Обработка активации/деактивации товара
    function updateProductCardFragments(card, fragments) {
        if (!card.length || !fragments) {
            return;
        }

        let updated = false;

        if (fragments.badge_html && card.find('.seller-product-card__badges').length) {
            card.find('.seller-product-card__badges').html(fragments.badge_html);
            updated = true;
        }

        if (fragments.menu_html && card.find('.seller-product-card__menu').length) {
            card.find('.seller-product-card__menu').html(fragments.menu_html);
            updated = true;
        }

        if (fragments.body_html && card.find('.seller-product-card__body').length) {
            card.find('.seller-product-card__body').html(fragments.body_html);
            updated = true;
        }

        if (!updated && fragments.card_html) {
            card.replaceWith(fragments.card_html);
        }
    }

    $(document).on('click', '.status-change-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const form = button.closest('.status-change-form');
        const action = form.data('action');
        const productName = form.data('product-name');

        let title, text, icon, confirmText;

        if (action === 'activate') {
            title = 'Включить товар?';
            text = `Товар "${productName}" снова станет доступен покупателям в каталоге и поиске.`;
            icon = 'question';
            confirmText = '<i class="uil uil-play me-1"></i> Да, включить';
        } else if (action === 'submit') {
            title = 'Отправить на модерацию?';
            text = `Товар "${productName}" будет отправлен на проверку администратору.`;
            icon = 'question';
            confirmText = '<i class="uil uil-shield-check me-1"></i> Да, отправить';
        } else {
            title = 'Скрыть товар?';
            text = `Товар "${productName}" будет скрыт из каталога и поиска.`;
            icon = 'warning';
            confirmText = '<i class="uil uil-pause me-1"></i> Да, скрыть';
        }

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: action === 'activate' ? '#28a745' : '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmText,
            cancelButtonText: 'Отмена',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    dataType: 'json',
                    data: form.serialize(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        const card = form.closest('.seller-product-card');
                        updateProductCardFragments(card, response.fragments || {});

                        Swal.fire({
                            icon: 'success',
                            title: 'Готово',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        console.log('Error response:', xhr);

                        let errorMessage = 'Не удалось изменить статус товара';

                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Ошибка',
                            html: errorMessage,
                            confirmButtonText: 'Понятно'
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '.push-up-btn', function(e) {
        e.preventDefault();
        const form = $(this).closest('.push-up-form');
        const productName = form.data('product-name');
        const pushAt = form.data('push-at');

        Swal.fire({
            title: 'Поднять товар?',
            html: `<p>Товар "<strong>${productName}</strong>" окажется на первых позициях в поиске и каталоге, как будто вы только что его добавили. Покупатели увидят его первым, но использовать эту возможность можно только один раз в день.</p>
               ${pushAt ? `<small class="text-muted">Последнее поднятие: ${pushAt}</small>` : ''}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#5F63F2',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Да, поднять',
            cancelButtonText: 'Отмена'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    dataType: 'json',
                    data: form.serialize(),
                    success: function(response) {
                        const card = form.closest('.seller-product-card');
                        updateProductCardFragments(card, response.fragments || {});

                        Swal.fire({
                            icon: 'success',
                            title: 'Готово!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Ошибка',
                            text: xhr.responseJSON?.error || 'Не удалось поднять товар'
                        });
                    }
                });
            }
        });
    });
});
</script>
@endpush
