{{-- Страница статистики магазина --}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')

<div class="contents">
   <div class="container-fluid">

      {{-- Заголовок --}}
      <div class="row mb-25">
         <div class="col-12">
            <div class="breadcrumb-main">
               <h4 class="text-capitalize breadcrumb-title">Статистика магазина</h4>
               <div class="breadcrumb-action justify-content-center flex-wrap">
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('account.index') }}">Кабинет</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Статистика</li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>

      {{-- ====================== --}}
      {{-- РЯД 1: Карточки-счётчики --}}
      {{-- ====================== --}}
      <div class="row">

         {{-- Всего товаров --}}
         <div class="col-xxl-3 col-xl-3 col-sm-6 mb-25">
            <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">
               <div class="overview-content w-100">
                  <div class="ap-po-details-content d-flex flex-wrap justify-content-between">
                     <div class="ap-po-details__titlebar">
                        <h1>{{ $totalProducts }}</h1>
                        <p>Всего товаров</p>
                     </div>
                     <div class="ap-po-details__icon-area">
                        <div class="svg-icon order-bg-opacity-primary color-primary">
                           <i class="uil uil-briefcase-alt"></i>
                        </div>
                     </div>
                  </div>
                  <div class="mt-2">
                     <small class="text-success me-2"><i class="uil uil-check-circle"></i> {{ $activeProducts }} активных</small>
                     <small class="text-muted"><i class="uil uil-eye-slash"></i> {{ $inactiveProducts }} скрытых</small>
                  </div>
               </div>
            </div>
         </div>

         {{-- VIP товаров --}}
         <div class="col-xxl-3 col-xl-3 col-sm-6 mb-25">
            <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">
               <div class="overview-content w-100">
                  <div class="ap-po-details-content d-flex flex-wrap justify-content-between">
                     <div class="ap-po-details__titlebar">
                        <h1>{{ $vipProducts }}</h1>
                        <p><a href="{{ route('account.vip.index') }}">VIP товаров активно</a></p>
                     </div>
                     <div class="ap-po-details__icon-area">
                        <div class="svg-icon order-bg-opacity-warning color-warning">
                           <i class="uil uil-star"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         {{-- Просмотры --}}
         <div class="col-xxl-3 col-xl-3 col-sm-6 mb-25">
            <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">
               <div class="overview-content w-100">
                  <div class="ap-po-details-content d-flex flex-wrap justify-content-between">
                     <div class="ap-po-details__titlebar">
                        <h1>{{ number_format($totalViews, 0, '.', ' ') }}</h1>
                        <p>Просмотров всего</p>
                     </div>
                     <div class="ap-po-details__icon-area">
                        <div class="svg-icon order-bg-opacity-info color-info">
                           <i class="uil uil-eye"></i>
                        </div>
                     </div>
                  </div>
                  <div class="mt-2">
                     <small class="text-muted">Сумма по всем товарам</small>
                  </div>
               </div>
            </div>
         </div>

         {{-- Баланс --}}
         <div class="col-xxl-3 col-xl-3 col-sm-6 mb-25">
            <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">
               <div class="overview-content w-100">
                  <div class="ap-po-details-content d-flex flex-wrap justify-content-between">
                     <div class="ap-po-details__titlebar">
                        <h1>{{ number_format($balance, 0, '.', ' ') }} ₽</h1>
                        <p>Баланс счёта</p>
                     </div>
                     <div class="ap-po-details__icon-area">
                        <div class="svg-icon order-bg-opacity-secondary color-secondary">
                           <i class="uil uil-usd-circle"></i>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>

      </div>{{-- end row 1 --}}


      {{-- ====================== --}}
      {{-- РЯД 2: Детали          --}}
      {{-- ====================== --}}
      <div class="row">

         {{-- ЛЕВАЯ КОЛОНКА --}}
         <div class="col-lg-8">

            {{-- Топ-5 товаров по просмотрам --}}
            <div class="card mb-25">
               <div class="card-header">
                  <h6 class="fw-500 mb-0"><i class="uil uil-chart-bar me-1"></i>Топ товаров по просмотрам</h6>
               </div>
               <div class="card-body p-0">
                  <div class="table-responsive">
                     <table class="table table-hover mb-0">
                        <thead class="table-light">
                           <tr>
                              <th class="ps-3">#</th>
                              <th>Товар</th>
                              <th class="text-end pe-3">Просмотры</th>
                           </tr>
                        </thead>
                        <tbody>
                           @forelse($topProducts as $i => $prod)
                           <tr>
                              <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                              <td>
                                 <a href="{{ route('account.store.products.show', $prod->id) }}" class="text-dark">
                                    {{ $prod->name }}
                                 </a>
                                 @if(!$prod->is_active)
                                    <span class="badge bg-secondary ms-1" style="font-size:10px;">скрыт</span>
                                 @endif
                              </td>
                              <td class="text-end pe-3">
                                 <strong>{{ number_format($prod->views, 0, '.', ' ') }}</strong>
                              </td>
                           </tr>
                           @empty
                           <tr>
                              <td colspan="3" class="text-center text-muted py-3">
                                 Нет данных о просмотрах
                              </td>
                           </tr>
                           @endforelse
                        </tbody>
                     </table>
                  </div>
               </div>
               @if($totalProducts > 5)
               <div class="card-footer bg-transparent border-top">
                  <a href="{{ route('account.store.products.index') }}" class="btn btn-sm btn-outline-secondary">
                     Все товары ({{ $totalProducts }})
                  </a>
               </div>
               @endif
            </div>

         </div>{{-- end col-lg-8 --}}

         {{-- ПРАВАЯ КОЛОНКА --}}
         <div class="col-lg-4">

            {{-- Краткая статистика товаров --}}
            <div class="card mb-25">
               <div class="card-header">
                  <h6 class="fw-500 mb-0"><i class="uil uil-apps me-1"></i>Товары по статусам</h6>
               </div>
               <div class="card-body">
                  <div class="list-group list-group-flush">
                     <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted fs-14"><i class="uil uil-briefcase-alt text-primary me-1"></i>Всего</span>
                        <span class="badge bg-primary rounded-pill">{{ $totalProducts }}</span>
                     </div>
                     <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted fs-14"><i class="uil uil-check-circle text-success me-1"></i>Активных</span>
                        <span class="badge bg-success rounded-pill">{{ $activeProducts }}</span>
                     </div>
                     <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted fs-14"><i class="uil uil-eye-slash text-secondary me-1"></i>Скрытых</span>
                        <span class="badge bg-secondary rounded-pill">{{ $inactiveProducts }}</span>
                     </div>
                     <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted fs-14"><i class="uil uil-star text-warning me-1"></i>VIP</span>
                        <span class="badge bg-warning text-dark rounded-pill">{{ $vipProducts }}</span>
                     </div>
                     <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted fs-14"><i class="uil uil-tag-alt text-danger me-1"></i>Со скидкой</span>
                        <span class="badge bg-danger rounded-pill">{{ $saleProducts }}</span>
                     </div>
                  </div>
               </div>
               <div class="card-footer bg-transparent border-top">
                  <a href="{{ route('account.store.products.index') }}" class="btn btn-sm btn-outline-primary w-100">
                     Управление товарами
                  </a>
               </div>
            </div>

         </div>{{-- end col-lg-4 --}}

      </div>{{-- end row 2 --}}

   </div>{{-- end container-fluid --}}
</div>{{-- end .contents --}}

@endsection
