{{-- ПРОСМОТР ТОВАРА --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

<div class="contents product-show-page">
    <div class="container-fluid">
        @if(!$seller)
            @include('backend.cabinet.store.nostore')
        @else
        @php
            $productCabinetState = $product->cabinet_state;
            $nextStepUrl = match ($productCabinetState->primaryAction) {
                'add_images', 'remove_vip' => route('account.store.products.edit', $product->id),
                'choose_package', 'choose_visible_products' => route('account.packages.index'),
                default => null,
            };
        @endphp
        <div class="row">
            <div class="col-lg-12">
                <div class="shop-breadcrumb">
                    <div class="breadcrumb-main">
                        <h4 class="breadcrumb-title">{{ $product->name }}</h4>
                        <div class="breadcrumb-action justify-content-center flex-wrap gap-2">
                            <a href="{{ route('account.store.products.index') }}" class="btn btn-sm btn-secondary">
                                <i class="uil uil-arrow-left"></i> К списку
                            </a>
                            <a href="{{ route('account.store.products.edit', $product) }}" class="btn btn-sm btn-primary">
                                <i class="uil uil-edit"></i> Редактировать
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="products product-show-page__content mb-30">
        <div class="container-fluid">
            <div class="product-show-overview">
                <div class="product-show-overview__main">
                    <span class="product-show-overview__eyebrow">Карточка товара</span>
                    <h5>{{ $product->name }}</h5>
                    <p>{{ $productCabinetState->description }}</p>
                </div>
                <div class="product-show-overview__status product-show-overview__status--{{ $productCabinetState->color }}">
                    <span>{{ $productCabinetState->label }}</span>
                    @if($productCabinetState->primaryActionLabel)
                        @if($nextStepUrl)
                            <a href="{{ $nextStepUrl }}">{{ $productCabinetState->primaryActionLabel }}</a>
                        @else
                            <strong>{{ $productCabinetState->primaryActionLabel }}</strong>
                        @endif
                    @endif
                </div>
            </div>
            <!-- Start: Card -->
            <div class="card h-100 border-0 product-show-card">
                <div class="product-item d-flex p-sm-50 p-20 product-show-layout">
                    <div class="row">
                        <div class="col-lg-5">
                            <!-- Start: Product Slider -->
                            <div class="product-item__image product-show-gallery">
                                @if($product->images->count() > 0)
                                    <div class="wrap-gallery-article carousel slide carousel-fade" id="carouselProductImages" data-bs-ride="carousel">
                                        <div>
                                            <div class="carousel-inner">
                                                @foreach($product->images as $index => $image)
                                                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                                        <img class="img-fluid d-flex bg-opacity-primary" 
                                                             src="{{ asset('storage/' . $image->path) }}" 
                                                             alt="{{ $product->name }}" 
                                                             style="width: 100%; height: 100%; object-fit: contain; background-color: #fafafa;">
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="overflow-hidden">
                                            <!-- Bottom switcher of slider -->
                                            <ul class="reset-ul d-flex flex-wrap list-thumb-gallery">
                                                @foreach($product->images as $index => $image)
                                                    <li class="position-relative">
                                                        <a href="#" class="thumbnail {{ $index === 0 ? 'active' : '' }}" 
                                                           data-bs-target="#carouselProductImages" 
                                                           data-bs-slide-to="{{ $index }}" 
                                                           aria-label="Slide {{ $index + 1 }}">
                                                            <img class="img-fluid d-flex" 
                                                                 src="{{ asset('storage/' . $image->path) }}" 
                                                                 alt="{{ $product->name }}">
                                                        </a>
                                                        @if($image->is_main)
                                                            <span class="position-absolute top-0 end-0 m-1 product-show-main-badge" style="background: rgba(95, 99, 242, 0.9); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                                                                <i class="uil uil-star" style="font-size: 14px;"></i>
                                                            </span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @else
                                    <div class="no-image-placeholder product-show-no-image text-center p-5 bg-light rounded">
                                        <i class="uil uil-image-slash" style="font-size: 80px; color: #adb5bd;"></i>
                                        <p class="text-muted mt-3">Нет изображений</p>
                                    </div>
                                @endif
                            </div>
                            <!-- End: Product Slider -->
                        </div>
                        <div class="col-lg-7">
                            <!-- Start: Product Details -->
                            <div class="b-normal-b mb-25 pb-sm-35 pb-15 mt-lg-0 mt-15">
                                <div class="product-item__body">
                                    <!-- Start: Product Title -->
                                    <div class="product-item__title">
                                        <a href="#">
                                            <h1 class="card-title">
                                                {{ $product->name }}
                                            </h1>
                                        </a>
                                    </div>
                                    <!-- End: Product Title -->

                                    <div class="product-item__content">
                                        <!-- Start: Product Category -->
                                        @if($product->category)
                                            <span class="product-details-brandName">Категория: <span>{{ $product->category->name }}</span></span>
                                        @endif
                                        <!-- Start: Product Status Badges -->
                                        <div class="d-flex gap-2 mb-3 mt-3 product-show-badges">
                                            @if($product->is_vip)
                                                <span class="badge badge-round badge-warning badge-lg">
                                                    <i class="uil uil-star"></i> VIP
                                                </span>
                                            @endif
                                            @if($product->view_state->isPublic)
                                                <span class="badge badge-round badge-success badge-lg">
                                                    <i class="uil uil-check"></i> Опубликован
                                                </span>
                                            @else
                                                <span class="badge badge-round badge-light badge-lg badge-outlined">
                                                    <i class="uil uil-pause"></i> Не опубликован
                                                </span>
                                            @endif
                                        </div>
                                        <x-product-moderation-status :product="$product" :has-issues="count($moderationIssues) > 0" />
                                        <x-product-moderation-issues :issues="$moderationIssues" />

                                        <!-- End: Product Status Badges -->
                                        

                                        
                                        <!-- Start: Product Price -->
                                        <div class="mb-20">
                                            @if($product->lowprice && $product->lowprice > 0)
                                                <span class="product-desc-price">
                                                    {{ number_format($product->lowprice, 0) }}<sub class="text-lowercase">&nbsp;руб.</sub>
                                                </span>
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="product-price">{{ number_format($product->price, 0) }}&nbsp;руб.</span>
                                                    <span class="product-discount">Скидка {{ round((($product->price - $product->lowprice) / $product->price) * 100) }}% </span>
                                                </div>
                                            @else
                                                <span class="product-desc-price">
                                                    {{ number_format($product->price, 0) }}<sub class="text-lowercase">&nbsp; руб.</sub>
                                                </span>
                                            @endif
                                        </div>
                                        <!-- End: Product Price -->

                                        <!-- Start: Product Description -->
                                        @if($product->desc)
                                            <div class="product-deatils-pera" style="white-space: pre-line;">{{ $product->desc }}</div>
                                        @endif
                                        <!-- End: Product Description -->

                                        <!-- Start: Product Characteristics -->
                                        @if($product->features1 || $product->features2 || $product->features3 || $product->features4)
                                            <div class="product-details__availability mb-3">
                                                <h6 class="mb-2">Характеристики:</h6>
                                                <ul class="list-unstyled">
                                                    @if($product->features1)
                                                        <li class="mb-2"><i class="uil uil-check-circle text-success me-1"></i> {{ $product->features1 }}</li>
                                                    @endif
                                                    @if($product->features2)
                                                        <li class="mb-2"><i class="uil uil-check-circle text-success me-1"></i> {{ $product->features2 }}</li>
                                                    @endif
                                                    @if($product->features3)
                                                        <li class="mb-2"><i class="uil uil-check-circle text-success me-1"></i> {{ $product->features3 }}</li>
                                                    @endif
                                                    @if($product->features4)
                                                        <li class="mb-2"><i class="uil uil-check-circle text-success me-1"></i> {{ $product->features4 }}</li>
                                                    @endif
                                                </ul>
                                            </div>
                                        @endif
                                        <!-- End: Product Characteristics -->
                                    {{-- Кнопки действий --}}
                                        <div class="mb-3 product-show-primary-action">
                                            <x-product-moderation-actions :product="$product" />
                                        </div>
                                        {{-- КНОПКИ УДАЛИТЬ И ИЗМЕНИТЬ --}}
                                        <div class="d-flex mt-20 flex-wrap gap-2 product-show-actions">
                                            @if($product->view_state->canUnpublish)
                                            <form action="{{ route('account.store.products.deactivate', $product) }}"
                                                  method="POST"
                                                  class="status-change-form flex-fill"
                                                  data-action="deactivate"
                                                  data-product-name="{{ $product->name }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="button"
                                                        class="btn btn-warning btn-default btn-squared status-change-btn w-100">
                                                    Снять с публикации
                                                </button>
                                            </form>
                                            @endif
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
                                            @if($product->view_state->primaryAction !== \App\Enums\ProductPrimaryAction::Edit)
                                            <a href="{{ route('account.store.products.edit', $product->id) }}" 
                                                class="btn btn-primary btn-default btn-squared border-0 flex-fill">
                                                Изменить
                                            </a>
                                            @endif
                                        </div>
                                    {{-- Конец кнопкам--}}


                                        
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Start: Additional Info -->
                            <div class="product-details__availability product-show-meta">
                                @if($product->push_at)
                                    <div class="title">
                                        <p>Поднятие:</p>
                                        <span class="free">{{ $product->push_at->format('d.m.Y в H:i') }}</span>
                                    </div>
                                @endif
                                <div class="title">
                                    <p>Создан:</p>
                                    <span class="free">{{ $product->created_at->format('d.m.Y в H:i') }}</span>
                                </div>
                                @if($product->updated_at != $product->created_at)
                                    <div class="title">
                                        <p>Редактирован:</p>
                                        <span class="free">{{ $product->updated_at->format('d.m.Y в H:i') }}</span>
                                    </div>
                                @endif
                            </div>
                            <!-- End: Additional Info -->
                            <!-- End: Product Details -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- End: Card -->
        </div>
        @endif
    </div>
</div>

@endsection

@push('styles')
<style>
.list-thumb-gallery .thumbnail {
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.list-thumb-gallery .thumbnail.is-main {
    border: 3px solid #5F63F2 !important;
    box-shadow: 0 0 8px rgba(95, 99, 242, 0.4) !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Настройка CSRF токена
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
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
            icon = 'warning';
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="uil uil-trash-alt me-1"></i> Да, удалить',
            cancelButtonText: 'Отмена',
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Удаление...',
                    text: 'Пожалуйста, подождите',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
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
            confirmText = '<i class="uil uil-check me-1"></i> Да, опубликовать';
        } else {
            title = 'Снять с публикации товар?';
            text = `Товар "${productName}" будет снят с публикации и скрыт от покупателей`;
            icon: 'warning',
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
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
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
                        let errorMessage = 'Произошла ошибка';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Ошибка',
                            html: errorMessage
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
