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
                        <p>VIP товаров активно</p>
                     </div>
                     <div class="ap-po-details__icon-area">
                        <div class="svg-icon order-bg-opacity-warning color-warning">
                           <i class="uil uil-star"></i>
                        </div>
                     </div>
                  </div>
                  @if($vipLimit !== null)
                  <div class="mt-2">
                     <small class="text-muted">Лимит: {{ $vipLimit }} VIP-мест</small>
                  </div>
                  @endif
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
                  @if($activePackage && $activePackage->package)
                  <div class="mt-2">
                     <small class="text-muted">Тариф: {{ $activePackage->package->price }} ₽/мес</small>
                  </div>
                  @endif
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

            {{-- Использование тарифа --}}
            <div class="card mb-25">
               <div class="card-header">
                  <h6 class="fw-500 mb-0"><i class="uil uil-layers-alt me-1"></i>Использование тарифа</h6>
               </div>
               <div class="card-body">
                  @if($activePackage && $activePackage->package)

                     {{-- Активные товары --}}
                     <div class="mb-20">
                        <div class="d-flex justify-content-between mb-1">
                           <span class="text-muted fs-14"><i class="uil uil-box text-primary me-1"></i>Активные товары</span>
                           @if($productLimit)
                              <strong class="fs-14">{{ $activeProducts }} / {{ $productLimit }}</strong>
                           @else
                              <strong class="fs-14 text-success">{{ $activeProducts }} / ∞</strong>
                           @endif
                        </div>
                        @if($productLimit)
                           @php $pct = $productLimit > 0 ? min(100, round($activeProducts / $productLimit * 100)) : 0; @endphp
                           <div class="progress" style="height: 8px;">
                              <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-primary') }}"
                                   role="progressbar"
                                   style="width: {{ $pct }}%"
                                   aria-valuenow="{{ $pct }}"
                                   aria-valuemin="0"
                                   aria-valuemax="100">
                              </div>
                           </div>
                           <small class="text-muted">{{ $pct }}% заполнено</small>
                        @else
                           <div class="progress" style="height: 8px;">
                              <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                           </div>
                           <small class="text-success">Безлимитный тариф</small>
                        @endif
                     </div>

                     {{-- VIP товары --}}
                     <div>
                        <div class="d-flex justify-content-between mb-1">
                           <span class="text-muted fs-14"><i class="uil uil-star text-warning me-1"></i>VIP товары</span>
                           @if($vipLimit !== null && $vipLimit > 0)
                              <strong class="fs-14">{{ $vipProducts }} / {{ $vipLimit }}</strong>
                           @elseif($vipLimit === 0)
                              <strong class="fs-14 text-danger">Недоступно</strong>
                           @else
                              <strong class="fs-14 text-success">{{ $vipProducts }} / ∞</strong>
                           @endif
                        </div>
                        @if($vipLimit !== null && $vipLimit > 0)
                           @php $vipPct = $vipLimit > 0 ? min(100, round($vipProducts / $vipLimit * 100)) : 0; @endphp
                           <div class="progress" style="height: 8px;">
                              <div class="progress-bar {{ $vipPct >= 90 ? 'bg-danger' : ($vipPct >= 70 ? 'bg-warning' : 'bg-warning') }}"
                                   role="progressbar"
                                   style="width: {{ $vipPct }}%">
                              </div>
                           </div>
                           <small class="text-muted">{{ $vipPct }}% заполнено</small>
                        @elseif($vipLimit === 0)
                           <div class="progress" style="height: 8px;">
                              <div class="progress-bar bg-secondary" role="progressbar" style="width: 100%"></div>
                           </div>
                           <small class="text-muted">VIP недоступно на текущем тарифе</small>
                        @else
                           <div class="progress" style="height: 8px;">
                              <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                           </div>
                           <small class="text-success">Безлимитный тариф</small>
                        @endif
                     </div>

                  @else
                     <div class="alert alert-warning mb-0">
                        <i class="uil uil-exclamation-triangle me-1"></i>
                        У вас нет активного тарифа. Товары не отображаются покупателям.
                        <a href="{{ route('account.packages.index') }}" class="alert-link ms-1">Выбрать тариф →</a>
                     </div>
                  @endif
               </div>
               @if($activePackage)
               <div class="card-footer bg-transparent border-top">
                  <a href="{{ route('account.packages.index') }}" class="btn btn-sm btn-outline-primary">
                     <i class="uil uil-arrow-circle-up"></i> Управление тарифом
                  </a>
               </div>
               @endif
            </div>

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

            {{-- История тарифов --}}
            <div class="card mb-25">
               <div class="card-header">
                  <h6 class="fw-500 mb-0"><i class="uil uil-history me-1"></i>История тарифов</h6>
               </div>
               <div class="card-body p-0">
                  <div class="table-responsive">
                     <table class="table table-hover mb-0">
                        <thead class="table-light">
                           <tr>
                              <th class="ps-3">Тариф</th>
                              <th>Активирован</th>
                              <th>Действует до</th>
                              <th class="pe-3">Статус</th>
                           </tr>
                        </thead>
                        <tbody>
                           @forelse($packageHistory as $sp)
                           @php
                              $isNow = $sp->is_active && ($sp->end_date === null || \Carbon\Carbon::parse($sp->end_date)->isFuture());
                           @endphp
                           <tr>
                              <td class="ps-3">
                                 {{ $sp->package ? $sp->package->name : '—' }}
                              </td>
                              <td>{{ $sp->start_date ? \Carbon\Carbon::parse($sp->start_date)->format('d.m.Y') : '—' }}</td>
                              <td>
                                 @if($sp->end_date)
                                    {{ \Carbon\Carbon::parse($sp->end_date)->format('d.m.Y') }}
                                 @else
                                    <span class="text-success">Бессрочно</span>
                                 @endif
                              </td>
                              <td class="pe-3">
                                 @if($isNow)
                                    <span class="btn btn-success btn-default btn-squared ">Активен</span>
                                 @elseif($sp->end_date && \Carbon\Carbon::parse($sp->end_date)->isPast())
                                    <span class="btn btn-warning btn-default btn-squared ">Истёк</span>
                                 @else
                                    <span class="btn btn-light btn-default btn-squared ">Неактивен</span>
                                 @endif
                              </td>
                           </tr>
                           @empty
                           <tr>
                              <td colspan="4" class="text-center text-muted py-3">
                                 История тарифов пуста
                              </td>
                           </tr>
                           @endforelse
                        </tbody>
                     </table>
                  </div>
               </div>
            </div>

         </div>{{-- end col-lg-8 --}}

         {{-- ПРАВАЯ КОЛОНКА --}}
         <div class="col-lg-4">

            {{-- Информация о тарифе --}}
            <div class="card mb-25">
               <div class="card-header">
                  <h6 class="fw-500 mb-0"><i class="uil uil-package me-1"></i>Текущий тариф</h6>
               </div>
               <div class="card-body">
                  @if($activePackage && $activePackage->package)

                     <div class="text-center mb-15 pb-15 border-bottom">
                        <h5 class="text-primary mb-5">{{ $activePackage->package->name }}</h5>
                        @if($activePackage->package->price > 0)
                           <span class="fs-18 fw-bold text-success">{{ number_format($activePackage->package->price, 0) }} ₽/мес</span>
                        @else
                           <span class="fs-18 fw-bold text-muted">Бесплатно</span>
                        @endif
                     </div>

                     @if($daysLeft !== null)
                        <div class="mb-15">
                           @if($daysLeft > 7)
                              <div class="alert alert-success py-2 px-3 mb-0">
                                 <i class="uil uil-clock me-1"></i>
                                 Осталось дней: <strong>{{ $daysLeft }}</strong>
                              </div>
                           @elseif($daysLeft >= 0)
                              <div class="alert alert-warning py-2 px-3 mb-0">
                                 <i class="uil uil-exclamation-triangle me-1"></i>
                                 Осталось: <strong>{{ $daysLeft }} {{ $daysLeft === 1 ? 'день' : ($daysLeft < 5 ? 'дня' : 'дней') }}</strong>
                              </div>
                           @else
                              <div class="alert alert-danger py-2 px-3 mb-0">
                                 <i class="uil uil-times-circle me-1"></i>
                                 Тариф истёк
                              </div>
                           @endif
                        </div>
                     @else
                        <div class="mb-15">
                           <div class="text-success text-center">
                              <i class="uil uil-infinity me-1"></i>
                              <strong>Бессрочный тариф</strong>
                           </div>
                        </div>
                     @endif

                     <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between">
                           <span class="text-muted fs-14">Активирован:</span>
                           <strong class="fs-14">{{ $activePackage->start_date ? \Carbon\Carbon::parse($activePackage->start_date)->format('d.m.Y') : '—' }}</strong>
                        </div>
                        @if($activePackage->end_date)
                        <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between">
                           <span class="text-muted fs-14">Истекает:</span>
                           <strong class="fs-14">{{ \Carbon\Carbon::parse($activePackage->end_date)->format('d.m.Y') }}</strong>
                        </div>
                        @endif
                        <div class="list-group-item px-0 py-2 border-0 d-flex justify-content-between">
                           <span class="text-muted fs-14">Автопродление:</span>
                           <strong class="fs-14 {{ $seller->auto_renew ? 'text-success' : 'text-muted' }}">
                              {{ $seller->auto_renew ? 'Включено' : 'Выключено' }}
                           </strong>
                        </div>
                     </div>

                  @else
                     <div class="text-center py-3">
                        <i class="uil uil-exclamation-circle text-warning fs-40 d-block mb-2"></i>
                        <p class="text-muted mb-3">Нет активного тарифа</p>
                        <a href="{{ route('account.packages.index') }}" class="btn btn-primary btn-sm">
                           Выбрать тариф
                        </a>
                     </div>
                  @endif
               </div>
            </div>

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
