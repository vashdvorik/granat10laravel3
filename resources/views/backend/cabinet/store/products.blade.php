{{-- КАТАЛОГ ТОВАРОВ --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

<div class="contents">
    <div class="container-fluid">
        @include('backend.cabinet.partials.flash')
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
                                'showPrimaryAction' => false,
                            ])
                        @endisset

                    <div class="seller-products-layout">

                        {{-- ФИЛЬТРЫ СВЕРХУ КАРТОЧЕК --}}
                        <aside class="seller-products-filters">
                            <div class="orders-toolbar seller-products-toolbar">
                                <div class="seller-products-toolbar__info">
                                    <strong>Фильтр по товарам</strong>
                                    <span>Показываем товары только этого магазина</span>
                                </div>
                                <div class="orders-status-filter seller-products-status-filter">
                                    <a href="{{ route('account.store.products.index') }}"
                                       class="{{ ($currentProductStatus ?? 'all') === 'all' ? 'is-active' : '' }}">
                                        Все
                                        <span>{{ $allProductsCount }}</span>
                                    </a>
                                    @foreach($productStatusFilters as $statusFilter)
                                        @if($statusFilter['key'] !== 'all')
                                            @php
                                                $statusQuery = request()->except('page');
                                                $statusQuery['product_status'] = $statusFilter['key'];
                                            @endphp
                                            <a href="{{ route('account.store.products.index', $statusQuery) }}"
                                               class="{{ ($currentProductStatus ?? 'all') === $statusFilter['key'] ? 'is-active' : '' }}">
                                                {{ $statusFilter['label'] }}
                                                <span>{{ $statusFilter['count'] }}</span>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="seller-products-categories-card">
                                <button
                                    class="seller-products-categories-card__head seller-products-categories-card__toggle collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#sellerProductsCategoriesCollapse"
                                    aria-expanded="false"
                                    aria-controls="sellerProductsCategoriesCollapse"
                                >
                                    <span class="seller-products-categories-card__head-text">
                                        <strong>Категории</strong>
                                        <span>Быстрый переход по каталогу магазина</span>
                                    </span>
                                    <span class="seller-products-categories-card__chevron" aria-hidden="true"></span>
                                </button>
                                <div class="seller-products-categories-card__body collapse" id="sellerProductsCategoriesCollapse">
                                    <div class="product-category">
                                        <ul class="category-list">
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
                                                        default => null,
                                                    };
                                                @endphp
                                                <div class="seller-product-card-col">
                                                    <div class="card product product--grid position-relative seller-product-card"
                                                         data-product-id="{{ $product->id }}"
                                                         data-card-signature="{{ $product->cabinet_state_signature }}">
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
                                                                        @if($product->isVipActive())
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

@endsection

@push('scripts')
<script>
$(document).ready(function() {
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

        if (fragments.card_html) {
            card.replaceWith(fragments.card_html);
            return;
        }

        if (fragments.badge_html && card.find('.seller-product-card__badges').length) {
            card.find('.seller-product-card__badges').html(fragments.badge_html);
        }

        if (fragments.menu_html && card.find('.seller-product-card__menu').length) {
            card.find('.seller-product-card__menu').html(fragments.menu_html);
        }

        if (fragments.body_html && card.find('.seller-product-card__body').length) {
            card.find('.seller-product-card__body').html(fragments.body_html);
        }
    }

    const sellerProductStatusRefreshUrl = "{{ route('account.store.products.refresh-statuses') }}";
    let sellerProductStatusRefreshInFlight = false;
    let sellerProductStatusRefreshTimer = null;

    function collectSellerProductCards() {
        return $('.seller-product-card[data-product-id]').map(function() {
            const card = $(this);

            return {
                id: Number(card.attr('data-product-id')),
                signature: card.attr('data-card-signature') || '',
            };
        }).get().filter((item) => Number.isFinite(item.id) && item.id > 0);
    }

    function refreshSellerProductCardStatuses() {
        const cards = collectSellerProductCards();

        if (!cards.length || sellerProductStatusRefreshInFlight) {
            return;
        }

        sellerProductStatusRefreshInFlight = true;

        $.ajax({
            url: sellerProductStatusRefreshUrl,
            method: 'GET',
            dataType: 'json',
            data: {
                product_ids: cards.map((item) => item.id),
                signatures: cards.reduce((carry, item) => {
                    carry[item.id] = item.signature;
                    return carry;
                }, {}),
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (!response || !Array.isArray(response.products)) {
                    return;
                }

                response.products.forEach((item) => {
                    const card = $(`.seller-product-card[data-product-id="${item.id}"]`);

                    if (!card.length || !item.card_html) {
                        return;
                    }

                    card.replaceWith(item.card_html);
                });
            },
            complete: function() {
                sellerProductStatusRefreshInFlight = false;
            }
        });
    }

    function startSellerProductStatusRefresh() {
        refreshSellerProductCardStatuses();

        if (sellerProductStatusRefreshTimer) {
            clearInterval(sellerProductStatusRefreshTimer);
        }

        sellerProductStatusRefreshTimer = setInterval(refreshSellerProductCardStatuses, 25000);
    }

    if ($('.seller-product-card[data-product-id]').length) {
        startSellerProductStatusRefresh();

        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                refreshSellerProductCardStatuses();
            }
        });

        window.addEventListener('focus', refreshSellerProductCardStatuses);
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
            title = 'Опубликовать товар?';
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
