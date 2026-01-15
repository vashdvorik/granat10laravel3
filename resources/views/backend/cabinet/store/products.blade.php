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
                                            <div class="product-category limit-list-item">
                                                <ul>
                                                    {{-- Получаем категории товаров текущего продавца --}}
                                                    @php
                                                        $userCategories = $products->pluck('category')->unique('id')->filter();
                                                    @endphp
                                                    
                                                    @if($userCategories->count() > 0)
                                                        @foreach($userCategories as $category)
                                                            <li>
                                                                <div class="w-100">
                                                                    <span class="fs-14 color-gray">
                                                                        {{ $category->name }}
                                                                        <span class="item-numbers">
                                                                            {{ $products->where('category_id', $category->id)->count() }}
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    @else
                                                        <li>
                                                            <div class="w-100">
                                                                <span class="fs-14 text-muted">Нет товаров</span>
                                                            </div>
                                                        </li>
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
                                                                    {{-- VIP бейдж --}}
                                                                    @if($product->is_vip)
                                                                        <span class="badge-vip">
                                                                            <i class="uil uil-star"></i> VIP
                                                                        </span>
                                                                    @endif
                                                                    
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

                                                                        {{-- Статус активности --}}
                                                                        <div class="mb-2">
                                                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                                                @if($product->is_active)
                                                                                    <span class="dm-tag tag-success tag-transparented">
                                                                                        <i class="uil uil-check"></i> Активен
                                                                                    </span>
                                                                                    {{-- Кнопка деактивации --}}
                                                                                    <form action="{{ route('account.store.products.deactivate', $product->id) }}" 
                                                                                          method="POST" 
                                                                                          class="status-change-form"
                                                                                          data-action="deactivate"
                                                                                          data-product-name="{{ $product->name }}">
                                                                                        @csrf
                                                                                        @method('PATCH')
                                                                                        <button type="button" 
                                                                                                class="btn btn-xs btn-outline-warning status-change-btn px-2 py-1"
                                                                                                style="font-size: 11px; line-height: 1.2;">
                                                                                            <i class="uil uil-pause" style="font-size: 12px;"></i> Остановить
                                                                                        </button>
                                                                                    </form>
                                                                                @else
                                                                                    <span class="dm-tag tag-danger tag-transparented">
                                                                                        <i class="uil uil-pause"></i> Неактивен
                                                                                    </span>
                                                                                    {{-- Кнопка активации --}}
                                                                                    <form action="{{ route('account.store.products.activate', $product->id) }}" 
                                                                                          method="POST" 
                                                                                          class="status-change-form"
                                                                                          data-action="activate"
                                                                                          data-product-name="{{ $product->name }}">
                                                                                        @csrf
                                                                                        @method('PATCH')
                                                                                        <button type="button" 
                                                                                                class="btn btn-xs btn-success status-change-btn px-2 py-1"
                                                                                                style="font-size: 11px; line-height: 1.2;">
                                                                                            <i class="uil uil-play" style="font-size: 12px;"></i> Активировать
                                                                                        </button>
                                                                                    </form>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    {{-- ЦЕНА --}}
                                                                    <div class="product-item__footer">
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

                                                                    {{-- КНОПКИ ДЕЙСТВИЙ --}}
                                                                    <div class="product-item__button d-flex mt-20 flex-wrap gap-2">
                                                                        {{-- Кнопка Удалить --}}
                                                                        <form action="{{ route('account.store.products.destroy', $product->id) }}" 
                                                                              method="POST" 
                                                                              class="delete-product-form"
                                                                              data-product-name="{{ $product->name }}">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="button" 
                                                                                    class="btn btn-default btn-squared color-light btn-outline-light px-20 delete-product-btn">
                                                                                 Удалить
                                                                            </button>
                                                                        </form>

                                                                        {{-- Кнопка Изменить --}}
                                                                        <a href="{{ route('account.store.products.edit', $product->id) }}" 
                                                                           class="btn btn-primary btn-default btn-squared border-0 px-25">
                                                                            Изменить
                                                                        </a>

 
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
    height: 250px;
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

/* VIP бейдж */
.badge-vip {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ffc107;
    color: #fff;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    z-index: 1;
}

/* Отступы между кнопками */
.product-item__button.gap-2 {
    gap: 0.5rem;
}
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
            title = 'Активировать товар?';
            text = `Товар "${productName}" будет активирован и станет доступен для покупателей`;
            icon = 'question';
            confirmText = '<i class="uil uil-play me-1"></i> Да, активировать';
        } else {
            title = 'Остановить товар?';
            text = `Товар "${productName}" будет деактивирован и скрыт от покупателей`;
            icon = 'warning';
            confirmText = '<i class="uil uil-pause me-1"></i> Да, остановить';
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
                        console.log('Success response:', response);
                        
                        // Находим контейнер со статусом
                        const statusContainer = form.parent();
                        const newAction = action === 'activate' ? 'deactivate' : 'activate';
                        const newUrl = form.attr('action').replace(action, newAction);
                        
                        // Создаем новый HTML в зависимости от действия
                        let newHtml = '';
                        if (action === 'activate') {
                            // Товар стал активным
                            newHtml = `
                                <span class="dm-tag tag-success tag-transparented">
                                    <i class="uil uil-check"></i> Активен
                                </span>
                                <form action="${newUrl}" 
                                      method="POST" 
                                      class="status-change-form"
                                      data-action="deactivate"
                                      data-product-name="${productName}">
                                    <input type="hidden" name="_token" value="${form.find('input[name="_token"]').val()}">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <button type="button" 
                                            class="btn btn-xs btn-outline-warning status-change-btn px-2 py-1"
                                            style="font-size: 11px; line-height: 1.2;">
                                        <i class="uil uil-pause" style="font-size: 12px;"></i> Остановить
                                    </button>
                                </form>
                            `;
                        } else {
                            // Товар стал неактивным
                            newHtml = `
                                <span class="dm-tag tag-danger tag-transparented">
                                    <i class="uil uil-pause"></i> Неактивен
                                </span>
                                <form action="${newUrl}" 
                                      method="POST" 
                                      class="status-change-form"
                                      data-action="activate"
                                      data-product-name="${productName}">
                                    <input type="hidden" name="_token" value="${form.find('input[name="_token"]').val()}">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <button type="button" 
                                            class="btn btn-xs btn-success status-change-btn px-2 py-1"
                                            style="font-size: 11px; line-height: 1.2;">
                                        <i class="uil uil-play" style="font-size: 12px;"></i> Активировать
                                    </button>
                                </form>
                            `;
                        }
                        
                        // Заменяем содержимое контейнера
                        statusContainer.html(newHtml);
                        
                        // Показываем уведомление об успехе
                        Swal.fire({
                            icon: 'success',
                            title: 'Выполнено',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        console.log('Error response:', xhr);
                        
                        Swal.close();
                        
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
});
</script>
@endpush