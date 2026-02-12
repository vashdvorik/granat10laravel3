{{-- КАТАЛОГ ТОВАРОВ --}}

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

            <div class="products_page product_page--grid mb-30">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        {{-- БОКОВАЯ ПАНЕЛЬ С КАТЕГОРИЯМИ --}}
                        <div class="columns-1 col-lg-3 col-sm-10 mb-lg-0 mb-30">
                            <div class="widget mb-lg-30 mt-25">  {{-- ДОБАВИЛ mt-25 --}}
                                <div class="widget-header-title px-20 py-15">
                                    <h6 class="d-flex align-content-center fw-500">
                                        <img src="{{ asset('cabinet/img/svg/sliders.svg') }}" alt="sliders" class="svg"> Фильтр
                                    </h6>
                                </div>
                                <div class="category_sidebar">
                                    <aside class="product-sidebar-widget mb-30">
                                        <div class="widget_title" data-bs-toggle="collapse" data-bs-target="#multiCollapseExample2" role="button" aria-expanded="true">
                                            <h6>Категории</h6>
                                        </div>
                                        <div class="card border-0 shadow-none multi-collapse mt-10 collapse show" id="multiCollapseExample2">
                                            <div class="product-category">
                                                <ul class="category-list">
                                                    {{-- Показать все товары --}}
                                                    <li>
                                                        <a href="{{ route('account.store.products.index') }}" 
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
                        </div>

                        {{-- ОСНОВНАЯ ОБЛАСТЬ С ТОВАРАМИ --}}
                        <div class="columns-2 col-lg-9">
                            <div class="tab-content mt-25" id="ap-tabContent">
                                <div class="tab-pane fade show active" id="ap-overview" role="tabpanel" aria-labelledby="ap-overview-tab">
                                    
                                    @if($products->count() > 0)
                                        {{-- КАРТОЧКИ ТОВАРОВ --}}
                                        <div class="row product-page-list justify-content-start">
                                            @foreach($products as $product)
                                                <div class="cus-xl-3 col-lg-4 col-md-6 col-12 mb-30 px-10">
                                                    <div class="card product product--grid">
                                                        <div class="h-100">
                                                            <div class="product-item">
                                                                {{-- ИЗОБРАЖЕНИЕ ТОВАРА --}}
                                                                <div class="product-item__image">
                                                                    {{-- Статусы: VIP и Активность --}}
                                                                    <div class="position-absolute top-0 start-0 m-2 d-flex gap-2">
                                                                        @if($product->is_vip)
                                                                            <span class="badge badge-round badge-danger badge-lg" style="font-size: 11px; font-weight: 600;">
                                                                                <i class="uil uil-star"></i> VIP
                                                                            </span>
                                                                        @endif
                                                                        
                                                                        @if($product->is_active)
                                                                            <span class="badge badge-round badge-success badge-lg" style="font-size: 11px; font-weight: 600;">
                                                                                <i class="uil uil-check"></i> Опубликован
                                                                            </span>
                                                                        @else
                                                                            <span class="badge badge-round badge-light badge-lg badge-outlined" style="font-size: 11px; font-weight: 600; background-color: #f8f9fa; color: #000; border-color: #dee2e6;">
                                                                                <i class="uil uil-pause"></i> Снят с публикации
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
                                                                <div class="card-body px-20 pb-25 pt-25">
                                                                    <div class="product-item__body">
                                                                        <a href="{{ route('account.store.products.show', $product->id) }}">
                                                                            <h6 class="card-title">{{ $product->name }}</h6>
                                                                        </a>
                                                                        
                                                                        {{-- Категория --}}
                                                                        @if($product->category)
                                                                            <small class="text-muted d-block mb-2">
                                                                                <i class="uil uil-tag-alt"></i> {{ $product->category->name }}
                                                                            </small>
                                                                        @endif

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

                                                                        {{-- Кнопки действий --}}
                                                                        <div class="mb-3">
                                                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                                                {{-- Кнопка Поднять --}}
                                                                                @if($product->is_active)
                                                                                    <form action="{{ route('account.store.products.push-up', $product->id) }}" 
                                                                                          method="POST" 
                                                                                          class="push-up-form m-0 flex-fill"
                                                                                          data-product-name="{{ $product->name }}"
                                                                                          data-push-at="{{ $product->push_at ? $product->push_at->format('d.m.Y H:i') : '' }}">
                                                                                        @csrf
                                                                                        <button type="button" 
                                                                                                class="btn {{ $product->push_at && $product->push_at->addHours(24)->isFuture() ? 'btn-light' : 'btn-sm btn-primary btn-transparent-primary' }} btn-default btn-squared push-up-btn w-100"
                                                                                                @if($product->push_at && $product->push_at->addHours(24)->isFuture())
                                                                                                    disabled
                                                                                                    title="Доступно {{ $product->push_at->addHours(24)->format('d.m.Y в H:i') }}"
                                                                                                @endif>
                                                                                            <i class="uil uil-arrow-up"></i> Поднять
                                                                                        </button>
                                                                                    </form>
                                                                                @endif

                                                                                {{-- Кнопка Опубликовать/Снять --}}
                                                                                @if($product->is_active)
                                                                                    <form action="{{ route('account.store.products.deactivate', $product->id) }}" 
                                                                                          method="POST" 
                                                                                          class="status-change-form m-0 flex-fill"
                                                                                          data-action="deactivate"
                                                                                          data-product-name="{{ $product->name }}">
                                                                                        @csrf
                                                                                        @method('PATCH')
                                                                                        <button type="button" 
                                                                                                class="btn btn-sm btn-warning btn-default btn-squared btn-transparent-warning status-change-btn w-100">
                                                                                            <i class="uil uil-pause"></i> Стоп
                                                                                        </button>
                                                                                    </form>
                                                                                @else
                                                                                    <form action="{{ route('account.store.products.activate', $product->id) }}" 
                                                                                          method="POST" 
                                                                                          class="status-change-form m-0 flex-fill"
                                                                                          data-action="activate"
                                                                                          data-product-name="{{ $product->name }}">
                                                                                        @csrf
                                                                                        @method('PATCH')
                                                                                        <button type="button" 
                                                                                                class="btn btn-sm btn-success btn-default btn-squared btn-transparent-success status-change-btn w-100">
                                                                                            <i class="uil uil-check"></i> Опубликовать
                                                                                        </button>
                                                                                    </form>
                                                                                @endif
                                                                            </div>
                                                                        </div>

                                                                        {{-- КНОПКИ УДАЛИТЬ И ИЗМЕНИТЬ --}}
                                                                        <div class="d-flex mt-20 flex-wrap gap-2">
                                                                            {{-- Кнопка Удалить --}}
                                                                            <form action="{{ route('account.store.products.destroy', $product->id) }}" 
                                                                                  method="POST" 
                                                                                  class="delete-product-form flex-fill"
                                                                                  data-product-name="{{ $product->name }}">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="button" 
                                                                                        class="btn btn-default btn-squared color-light btn-outline-light delete-product-btn w-100">
                                                                                     Удалить
                                                                                </button>
                                                                            </form>

                                                                            {{-- Кнопка Изменить --}}
                                                                            <a href="{{ route('account.store.products.edit', $product->id) }}" 
                                                                               class="btn btn-primary btn-default btn-squared border-0 flex-fill">
                                                                                Изменить
                                                                            </a>
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
                                                <div class="card">
                                                    <div class="card-body text-center py-5">
                                                        <i class="uil uil-box fs-40 text-muted mb-3 d-block"></i>
                                                        <h5 class="text-muted mb-3">У вас пока нет товаров</h5>
                                                        <p class="text-muted mb-4">Добавьте первый товар, чтобы начать продажи</p>
                                                        <a href="{{ route('account.store.products.create') }}" 
                                                           class="btn btn-primary">
                                                            <i class="uil uil-plus"></i> Добавить первый товар
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ДОПОЛНИТЕЛЬНЫЕ СТИЛИ --}}
<style>
/* Placeholder для товаров без изображения */
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
</style>
</style>
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
                <p>Вы уверены, что хотите удалить товар</p>
                <h5 class="mt-2 mb-2 text-danger">${productName}</h5>
                <p class="text-muted mb-0">Это действие невозможно отменить</p>
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
    $(document).on('click', '.status-change-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const form = button.closest('.status-change-form');
        const action = form.data('action');
        const productName = form.data('product-name');
        
        let title, text, icon, confirmText;
        
        if (action === 'activate') {
            title = 'Опубликовать товар?';
            text = `Товар "${productName}" будет опубликован и станет доступен для покупателей`;
            icon = 'question';
            confirmText = '<i class="uil uil-play me-1"></i> Да, опубликовать';
        } else {
            title = 'Снять с публикации товар?';
            text = `Товар "${productName}" будет снят с публикации и скрыт от покупателей`;
            icon = 'warning';
            confirmText = '<i class="uil uil-pause me-1"></i> Да, снять с публикации';
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
                // Отправляем AJAX запрос
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        // Просто перезагружаем страницу
                        Swal.fire({
                            icon: 'success',
                            title: 'Выполнено',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        console.log('Error response:', xhr);
                        
                        let errorMessage = 'Произошла ошибка';
                        
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
// Обработчик поднятия товара
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
                data: form.serialize(),
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Готово!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
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