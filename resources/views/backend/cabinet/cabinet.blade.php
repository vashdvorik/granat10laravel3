{{--Главная страница в кабинете продавца--}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif


<div class="contents">

   <div class="crm mb-25">
      <div class="container-fluid">
         
         
      
        
         {{--
         <div class="row">
         
                  @if(!$seller)
                  @include('backend.cabinet.store.nostore')
                  @else 
         
            <div class="col-lg-12">

               <div class="breadcrumb-main">
                  <h4 class="text-capitalize breadcrumb-title">Магазин: {{ $seller->name }}</h4>
                  <div class="breadcrumb-action justify-content-center flex-wrap">
                  </div>
               </div>
            
            </div>
            <div class="col-xxl-6">
               <div class="row">
                  <div class="col-xxl-6 col-sm-6 mb-25">

                     <!-- Card 1  -->
                     <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">

                        <div class="overview-content w-100">
                           <div class=" ap-po-details-content d-flex flex-wrap justify-content-between">
                              <div class="ap-po-details__titlebar">
                                 <h1>{{ $seller->products()->count() }}</h1>
                                 <p>Активных товаров</p>
                              </div>
                              <div class="ap-po-details__icon-area">
                                 <div class="svg-icon order-bg-opacity-primary color-primary">

                                    <i class="uil uil-briefcase-alt"></i>
                                 </div>
                              </div>
                           </div>
                        </div>

                     </div>
                     <!-- Card 1 End  -->
                  </div>

                  <div class="col-xxl-6 col-sm-6 mb-25">
                     <!-- Card 2 -->
                     <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">

                        <div class="overview-content w-100">
                           <div class=" ap-po-details-content d-flex flex-wrap justify-content-between">
                              <div class="ap-po-details__titlebar">
                                 <h1>{{ $seller->products()->where('is_vip', true)->count() }}</h1>
                                 <p>VIP товаров</p>

                              </div>
                              <div class="ap-po-details__icon-area">
                                 <div class="svg-icon order-bg-opacity-info color-info">

                                    <i class="uil uil-shopping-cart-alt"></i>
                                 </div>
                              </div>
                           </div>
                        </div>

                     </div>
                     <!-- Card 2 End  -->
                  </div>
                  <div class="col-xxl-6 col-sm-6 mb-25">
                     <!-- Card 3 -->
                     <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">

                        <div class="overview-content w-100">
                           <div class=" ap-po-details-content d-flex flex-wrap justify-content-between">
                              <div class="ap-po-details__titlebar">
                                 <h1>{{ auth()->user()->balance }} руб.</h1>
                                 <p>На счету</p>
                              </div>
                              <div class="ap-po-details__icon-area">
                                 <div class="svg-icon order-bg-opacity-secondary color-secondary">

                                    <i class="uil uil-usd-circle"></i>
                                 </div>
                              </div>
                           </div>

                        </div>

                     </div>
                     <!-- Card 3 End  -->
                  </div>

                  <div class="col-xxl-6 col-sm-6 mb-25">
                     <!-- Card 4  -->
                     <div class="ap-po-details ap-po-details--2 p-25 radius-xl d-flex justify-content-between">

                        <div class="overview-content w-100">
                           <div class=" ap-po-details-content d-flex flex-wrap justify-content-between">
                              <div class="ap-po-details__titlebar">
                                 <h1>50</h1>
                                 <p>Просмотров за месяц</p>
                              </div>
                              <div class="ap-po-details__icon-area">
                                 <div class="svg-icon order-bg-opacity-warning color-warning">

                                    <i class="uil uil-users-alt"></i>
                                 </div>
                              </div>
                           </div>
                        </div>

                     </div>
                     <!-- Card 4 End  -->
                  </div>

               </div>
            </div>

            <div class="col-xxl-6 col-lg-6 mb-25">

               <div class="card border-0 px-3 px-md-4 px-lg-5 browser-states">
                  <div class="card-header px-0 border-0 pt-3">
                     <h6>Ваш тариф</h6>
                     <div class="card-extra">
                        <ul class="card-tab-links nav-tabs nav" role="tablist">
                           <li>
                              <a class="active" href="#t_selling-today2" data-bs-toggle="tab" id="t_selling-today2-tab" role="tab" aria-selected="true">Подробнее</a>
                           </li>
                        </ul>
                     </div>
                  </div>
                  <div class="card-body p-0 pt-3">
                     <div class="tab-content">
                        <div class="tab-pane fade active show" id="t_selling-today2" role="tabpanel" aria-labelledby="t_selling-today2-tab">
                           <div class="selling-table-wrap">
                              <div class="table-responsive">
                                 @php
                                    $activePackage = $seller->activePackage();
                                 @endphp
                                 <table class="table table--default table-borderless">
                                    <thead>
                                       <tr>
                                          <th>Тариф </th>
                                          <th>Цена</th>
                                          <th>Активирован</th>
                                          <th>Действует до</th>
                                       </tr>
                                    </thead>
                                    <tbody>
                                       <tr>
                                          @if($activePackage)
                                          <td>
                                             <div class="selling-product-img d-flex align-items-center">
                                                   <span>{{ $activePackage->package->name }}</span>
                                             </div>
                                          </td>
                                          <td>{{ $activePackage->package->price }}</td>
                                          <td>{{ $activePackage->start_date->format('d.m.Y') }}</td>
                                          <td>{{ $activePackage->end_date ? $activePackage->end_date->format('d.m.Y') : 'Бессрочно' }}</td>
                                          @else
                                             <span>Нет активного тарифа</span>
                                          @endif
                                       </tr>
                        
                                    </tbody>
                                 </table>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>

            </div>
            
         </div>
         --}}
         @if(!$seller)
         @include('backend.cabinet.store.nostore')
         @else 
         <!-- ends: .row -->

         <div class="row mt-25">
               {{-- Основная информация --}}
               <div class="col-lg-8">
                  {{-- Уведомление о статусе пакета --}}
                  @if($seller)
                     @include('components.package-status-alert', ['seller' => $seller])
                  @endif
                  <div class="card mb-25">
                     <div class="card-header">
                           <h6 class="fw-500 mb-0">Основная информация</h6>
                     </div>
                     <div class="card-body">
                           <div class="row g-3">
                              <div class="col-md-6">
                                 <div class="p-3 border rounded bg-light-subtle">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-store text-primary fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Название магазина</small>
                                             <strong class="d-block">{{ $seller->name }}</strong>
                                          </div>
                                       </div>
                                 </div>
                              </div>

                              <div class="col-md-6">
                                 <div class="p-3 border rounded bg-light-subtle">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-phone text-success fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Телефон магазина</small>
                                             <strong class="d-block">{{ $seller->phone ?? 'Не указан' }}</strong>
                                          </div>
                                       </div>
                                 </div>
                              </div>

                              <div class="col-md-6">
                                 <div class="p-3 border rounded bg-light-subtle">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-map-marker text-danger fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Город</small>
                                             <strong class="d-block">{{ $seller->town ?? 'Не указан' }}</strong>
                                          </div>
                                       </div>
                                 </div>
                              </div>

                              <div class="col-md-6">
                                 <div class="p-3 border rounded bg-light-subtle">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-location-point text-info fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Район</small>
                                             <strong class="d-block">{{ $seller->sellerplace ?? 'Не указан' }}</strong>
                                          </div>
                                       </div>
                                 </div>
                              </div>

                              <div class="col-12">
                                 <div class="p-3 border rounded bg-light-subtle">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-map-pin-alt text-warning fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Адрес</small>
                                             <strong class="d-block">{{ $seller->address ?? 'Не указан' }}</strong>
                                          </div>
                                       </div>
                                 </div>
                              </div>

                              @if($seller->description)
                              <div class="col-12">
                                 <div class="p-3 border rounded bg-light-subtle">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-file-alt text-secondary fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Описание</small>
                                             <p class="mb-0">{{ $seller->description }}</p>
                                          </div>
                                       </div>
                                 </div>
                              </div>
                              @endif
                           </div>
                     </div>
                     <div class="card-footer bg-transparent border-top">
                           <div class="d-flex justify-content-end">
                              <a href="{{ route('account.store.edit', $seller->id) }}" class="btn btn-sm btn-outline-primary">
                                 <i class="uil uil-edit"></i> Редактировать
                              </a>
                           </div>
                     </div>
                  </div>

                  {{-- Социальные сети и контакты --}}
                  <div class="card mb-25">
                     <div class="card-header">
                           <h6 class="fw-500 mb-0">Контакты и соцсети</h6>
                     </div>
                     <div class="card-body">
                           @php
                              $activePackage = $seller->activePackage();
                              $hasWebsiteLink = $activePackage && $activePackage->package && $activePackage->package->has_website_link;
                              $hasSocialLinks = $activePackage && $activePackage->package && $activePackage->package->has_social_links;
                           @endphp

                           {{-- Сообщение об ограничениях тарифа --}}
                           @if(!$hasWebsiteLink || !$hasSocialLinks)
                              <div class="alert alert-warning d-flex align-items-center mb-3">
                                 <i class="uil uil-exclamation-triangle me-2"></i>
                                 <div>
                                       <strong>Ограничения тарифа:</strong>
                                       @if(!$hasWebsiteLink) Ссылка на сайт недоступна. @endif
                                       @if(!$hasSocialLinks) Подключение соцсетей недоступно. @endif
                                       
                                 </div>
                              </div>
                           @endif

                           <div class="row g-3">
                              <div class="col-md-6">
                                 <div class="p-3 border rounded">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-globe text-primary fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Веб-сайт</small>
                                             @if(!$hasWebsiteLink)
                                                   <span class="text-danger fw-500"><i class="uil uil-lock-alt"></i> Недоступно</span>
                                             @elseif($seller->link)
                                                   <a href="{{ $seller->link }}" target="_blank" class="fw-500">
                                                      {{ Str::limit($seller->link, 30) }} ↗
                                                   </a>
                                             @else
                                                   <span class="text-muted">Не указан</span>
                                             @endif
                                          </div>
                                       </div>
                                 </div>
                              </div>

                              <div class="col-md-6">
                                 <div class="p-3 border rounded">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-map text-danger fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Google Maps</small>
                                             {{-- Карта доступна всем --}}
                                             @if($seller->link_map)
                                                   <a href="{{ $seller->link_map }}" target="_blank" class="fw-500">
                                                      Открыть на карте ↗
                                                   </a>
                                             @else
                                                   <span class="text-muted">Не указана</span>
                                             @endif
                                          </div>
                                       </div>
                                 </div>
                              </div>

                              <div class="col-md-4">
                                 <div class="p-3 border rounded">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-telegram text-info fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Telegram</small>
                                             @if(!$hasSocialLinks)
                                                   <span class="text-danger fw-500"><i class="uil uil-lock-alt"></i> Недоступно</span>
                                             @elseif($seller->telegram)
                                                   <strong>{{ $seller->telegram }}</strong>
                                             @else
                                                   <span class="text-muted">Не указан</span>
                                             @endif
                                          </div>
                                       </div>
                                 </div>
                              </div>

                              <div class="col-md-4">
                                 <div class="p-3 border rounded">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-instagram text-danger fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Instagram</small>
                                             @if(!$hasSocialLinks)
                                                   <span class="text-danger fw-500"><i class="uil uil-lock-alt"></i> Недоступно</span>
                                             @elseif($seller->instagram)
                                                   <strong>{{ $seller->instagram }}</strong>
                                             @else
                                                   <span class="text-muted">Не указан</span>
                                             @endif
                                          </div>
                                       </div>
                                 </div>
                              </div>

                              <div class="col-md-4">
                                 <div class="p-3 border rounded">
                                       <div class="d-flex align-items-start">
                                          <div class="me-3">
                                             <i class="uil uil-phone-volume text-success fs-24"></i>
                                          </div>
                                          <div class="flex-grow-1">
                                             <small class="text-muted d-block mb-1">Viber</small>
                                             @if(!$hasSocialLinks)
                                                   <span class="text-danger fw-500"><i class="uil uil-lock-alt"></i> Недоступно</span>
                                             @elseif($seller->viber)
                                                   <strong>{{ $seller->viber }}</strong>
                                             @else
                                                   <span class="text-muted">Не указан</span>
                                             @endif
                                          </div>
                                       </div>
                                 </div>
                              </div>
                           </div>
                     </div>
                     <div class="card-footer bg-transparent border-top">
                           <div class="d-flex justify-content-end gap-2">
                                 @if(!$hasWebsiteLink || !$hasSocialLinks)
                                       <a href="{{ route('account.packages.index') }}" class="btn btn-sm btn-warning">
                                          <i class="uil uil-arrow-circle-up"></i> Обновить тариф
                                       </a>
                                 @endif                                
                                 <a href="{{ route('account.store.edit', $seller->id) }}" class="btn btn-sm btn-outline-primary">
                                       <i class="uil uil-edit"></i> Редактировать
                                 </a>

                           </div>
                     </div>
                  </div>
               </div>

               {{-- Боковая панель --}}
               <div class="col-lg-4">


                  {{-- Активный тариф --}}
                  @php
                     $activePackage = $seller->activePackage();
                  @endphp
                  <div class="card mb-25">
                     <div class="card-header color-dark fw-500">
                           <h6 class="fw-500">Текущий тариф</h6>
                     </div>
                     <div class="card-body">
                           @if($activePackage && $activePackage->package)
                              <div class="text-center mb-20 pb-20 border-bottom">
                                 <h4 class="mb-10 text-primary">{{ $activePackage->package->name }}</h4>
                                 @if($activePackage->package->price > 0)
                                       <div class="fs-20 fw-bold text-success">{{ number_format($activePackage->package->price, 0) }} ₽</div>
                                 @else
                                       <div class="fs-20 fw-bold text-muted">Бесплатно</div>
                                 @endif
                              </div>

                              {{-- Сроки действия --}}
                              <div class="mb-20 pb-20 border-bottom">
                                 <div class="mb-10">
                                       <small class="text-muted d-block mb-1">Дата активации:</small>
                                       <strong>{{ $activePackage->start_date ? \Carbon\Carbon::parse($activePackage->start_date)->format('d.m.Y') : 'N/A' }}</strong>
                                 </div>

                                 @if($activePackage->end_date)
                                       <div class="mb-10">
                                          <small class="text-muted d-block mb-1">Дата окончания:</small>
                                          <strong>{{ \Carbon\Carbon::parse($activePackage->end_date)->format('d.m.Y') }}</strong>
                                       </div>
                                       @php
                                          $daysLeft = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($activePackage->end_date), false);
                                       @endphp
                                       @if($daysLeft > 0)
                                          <div class="alert alert-info py-2 px-3 mb-0">
                                             <small><i class="uil uil-clock"></i> Осталось дней: <strong>{{ $daysLeft }}</strong></small>
                                          </div>
                                       @elseif($daysLeft == 0)
                                          <div class="alert alert-warning py-2 px-3 mb-0">
                                             <small><i class="uil uil-exclamation-triangle"></i> Истекает сегодня!</small>
                                          </div>
                                       @else
                                          <div class="alert alert-danger py-2 px-3 mb-0">
                                             <small><i class="uil uil-times-circle"></i> Тариф истёк</small>
                                          </div>
                                       @endif
                                 @else
                                       <div class="mb-10">
                                          <div class="text-success">
                                             <i class="uil uil-infinity me-1"></i>
                                             <strong>Бессрочный тариф</strong>
                                          </div>
                                       </div>
                                 @endif

                                 @if($activePackage->package->duration_days)
                                       <div class="mt-10">
                                          <small class="text-muted">Длительность тарифа: <strong>{{ $activePackage->package->duration_days }} дней</strong></small>
                                       </div>
                                 @endif
                              </div>

                              {{-- Лимиты товаров --}}
                              <div class="mb-20 pb-20 border-bottom">
                                 <h6 class="fw-500 mb-15">Лимиты товаров</h6>
                                 
                                 <div class="d-flex justify-content-between align-items-center mb-10">
                                       <span class="text-muted">
                                          <i class="uil uil-box text-primary"></i> Активных товаров:
                                       </span>
                                       <strong>
                                          @if($activePackage->package->active_products_limit)
                                             {{ $seller->product_count ?? 0 }} / {{ $activePackage->package->active_products_limit }}
                                          @else
                                             <span class="text-success">Безлимит</span>
                                          @endif
                                       </strong>
                                 </div>

                                 <div class="d-flex justify-content-between align-items-center">
                                       <span class="text-muted">
                                          <i class="uil uil-star text-warning"></i> VIP-товаров:
                                       </span>
                                       <strong>
                                          @if($activePackage->package->vip_products_limit !== null)
                                             0 / {{ $activePackage->package->vip_products_limit }}
                                          @else
                                             <span class="text-success">Безлимит</span>
                                          @endif
                                       </strong>
                                 </div>
                              </div>

                              {{-- Возможности тарифа --}}
                              <div class="mb-20">
                                 <h6 class="fw-500 mb-15">Возможности</h6>
                                 
                                 <div class="list-group list-group-flush">
                                       <div class="list-group-item px-0 py-2 border-0">
                                          @if($activePackage->package->has_website_link)
                                             <i class="uil uil-check-circle text-success"></i>
                                             <span class="text-success">Ссылка на сайт</span>
                                          @else
                                             <i class="uil uil-times-circle text-muted"></i>
                                             <span class="text-muted">Ссылка на сайт</span>
                                          @endif
                                       </div>

                                       <div class="list-group-item px-0 py-2 border-0">
                                          @if($activePackage->package->has_social_links)
                                             <i class="uil uil-check-circle text-success"></i>
                                             <span class="text-success">Подключение соцсетей</span>
                                          @else
                                             <i class="uil uil-times-circle text-muted"></i>
                                             <span class="text-muted">Подключение соцсетей</span>
                                          @endif
                                       </div>

                                       <div class="list-group-item px-0 py-2 border-0">
                                          @if($activePackage->package->has_order_system)
                                             <i class="uil uil-check-circle text-success"></i>
                                             <span class="text-success">Прием заказов</span>
                                          @else
                                             <i class="uil uil-times-circle text-muted"></i>
                                             <span class="text-muted">Прием заказов</span>
                                          @endif
                                       </div>
                                 </div>
                              </div>

                              {{-- Кнопка смены тарифа --}}
                              <a href="{{ route('account.packages.index') }}" class="btn btn-outline-primary btn-sm w-100">
                                 <i class="uil uil-refresh"></i> Сменить тариф
                              </a>
                           @else
                              <div class="text-center py-4">
                                 <i class="uil uil-exclamation-triangle fs-40 text-warning mb-3"></i>
                                 <p class="text-muted mb-3">Нет активного тарифа</p>
                                 <a href="{{ route('account.packages.index') }}" class="btn btn-primary btn-sm">
                                       <i class="uil uil-plus"></i> Выбрать тариф
                                 </a>
                              </div>
                           @endif
                     </div>
                  </div>
               </div>
         </div>
         <!-- ends: .row -->
         @endif
      </div>
   </div>

</div>
@endsection
