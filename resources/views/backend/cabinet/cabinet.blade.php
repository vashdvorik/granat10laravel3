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
         <div class="row ">
         
                  @if(!$seller)
                  {{-- вверхний  --}}
                  <div class="col-12">
                     <div class="breadcrumb-main">
                        <h4 class="text-capitalize breadcrumb-title"></h4>
                        <div class="breadcrumb-action justify-content-center flex-wrap">
                        </div>
                     </div>
                  </div>
                  {{-- баннер создать магазин --}}
                  <div class="banner-feature--14 card mb-25">
                     <div class="row justify-content-center">
                        <div class="col-md-9">
                           <div class="row align-items-center">
                              <div class="col-md-6">
                                 <div class="card-body d-inline-block">
                                    <h1 class="d-flex">Привет, Итак начнем торговать</h1>
                                    <p>Для начала нужно создать магазин и заполнить информацию о нем. После создания магазина вы сможете добавлять товары и управлять ими.
                                    </p>
                                    <div class="dm-empty__action">
                                    <a href="{{ route('cabinet.create_shop') }}" class="btn btn-primary btn-sm btn-squared">
                                       Создать магазин
                                    </a>
                                    </div>
                                 </div>
                              </div>
                              <div class="col-md-6">
                                 <div class="banner-feature__shape mt-50 d-flex justify-content-end">
                                    <img src="{{ asset('cabinet/img/svg/banne-group21.svg') }}" alt="img" class="svg">
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>

                  {{-- Помощь и информация --}}
                  <div class="card banner-feature--16 mb-50  px-xxl-0 px-sm-30 px-15">
                     <div class="row justify-content-center">
                        <div class="col-xxl-9">
                           <div class="row">
                              <div class="col-xxl-4 col-lg-6 mb-25">
                                 <div class="card shadow-none border-0">
                                    <div class="card-body banner-feature--15">
                                       <div class="banner-feature__shape d-flex justify-content-center">
                                          <div class="wh-80 bg-primary rounded-circle content-center">
                                             <img src="{{ asset('cabinet/img/svg/idea.svg') }}" alt="img" class="svg">
                                          </div>
                                       </div>
                                       <div class="pb-md-0 pb-30 text-center">
                                          <h4>Помощь</h4>
                                          <p>Ответы на часто задаваемые вопросы.</p>
                                       </div>
                                       <div class="content-center mt-25">
                                          <button class="btn btn-primary btn-sm btn-squared btn-transparent-primary rounded-pill">Learn
                                             More</button>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <div class="col-xxl-4 col-lg-6 mb-25">
                                 <div class="card shadow-none border-0">
                                    <div class="card-body banner-feature--15">
                                       <div class="banner-feature__shape d-flex justify-content-center">
                                          <div class="wh-80 bg-info rounded-circle content-center">
                                             <img src="{{ asset('cabinet/img/svg/chat.svg') }}" alt="img" class="svg">
                                          </div>
                                       </div>
                                       <div class="pb-md-0 pb-30 text-center">
                                          <h4>FAQ</h4>
                                          <p>Ответы на часто задаваемые вопросы.</p>
                                       </div>
                                       <div class="content-center mt-25">
                                          <button class="btn btn-primary btn-sm btn-squared btn-transparent-primary rounded-pill">Learn
                                             More</button>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <div class="col-xxl-4 col-lg-6 mb-25">
                                 <div class="card shadow-none border-0">
                                    <div class="card-body banner-feature--15">
                                       <div class="banner-feature__shape d-flex justify-content-center">
                                          <div class="wh-80 bg-success rounded-circle content-center">
                                             <img src="{{ asset('cabinet/img/svg/documents.svg') }}" alt="img" class="svg">
                                          </div>
                                       </div>
                                       <div class="pb-md-0 pb-30 text-center">
                                          <h4>Тарифы сайта</h4>
                                          <p>Узнайте о тарифах сайта, откройте для себя больше возможностей.</p>
                                       </div>
                                       <div class="content-center mt-25">
                                          <button class="btn btn-primary btn-sm btn-squared btn-transparent-primary rounded-pill">Learn
                                             More</button>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>

                  @else 
         
            <div class="col-lg-12">

               <div class="breadcrumb-main">
                  <h4 class="text-capitalize breadcrumb-title">Магазин: {{ $seller->name }}</h4>
                  <div class="breadcrumb-action justify-content-center flex-wrap">
                  <!--   <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                           <li class="breadcrumb-item"><a href="#"><i class="uil uil-estate"></i>Dashboard</a></li>
                           <li class="breadcrumb-item active" aria-current="page">Demo 1</li>
                        </ol>
                     </nav> -->
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
                                 <h1>5</h1>
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
                                 <h1>{{ $seller->balance }} руб.</h1>
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
                                          <th>Дата подключения</th>
                                          <th>Дата окончания</th>
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
                                          <td>{{ $activePackage->end_date->format('d.m.Y') }}</td>
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
         <!-- ends: .row -->
         <div class="col-12">
            <div class="card card-default card-md mb-4">
               <div class="card-header">
                  <h6>Информация о магазине</h6>
               </div>
               <div class="card-body">
                  <div class="columnCard-wrapper">
                     <div class="row">
                        <div class="col-lg-4 mb-20">


                           <div class="card card-default card-md bg- ">
                              <div class="card-header">
                                 <h6>Основная</h6>

                              </div>
                              <div class="card-body">
                                 <div class="card-content">
                                    <p><strong>Название:</strong> {{ $seller->name }}</p>
                                    <p><strong>Описание:</strong>{{ $seller->description }}</p>
                                    <p><strong>Район:</strong> {{ $seller->sellerplace }}</p>
                                    <p><strong>Адрес:</strong> {{ $seller->address }}</p> 
                                 </div>
                                 <button class="btn btn-light btn-default btn-squared ">Редактировать</button>
                              </div>
                           </div>



                        </div>
                        <div class="col-lg-4 mb-20">


                           <div class="card card-default card-md bg- ">
                              <div class="card-header">
                                 <h6>Контакты</h6>

                              </div>
                              <div class="card-body">
                                 <div class="card-content">
                                    <p><strong>Телефон:</strong>{{ $seller->phone ?? 'Не указан' }}</p>
                                    <p><strong>Телеграм:</strong> {{ $seller->telegram ?? 'Не указан' }}</p>
                                    <p><strong>Instagram:</strong> {{ $seller->instagram ?? 'Не указан' }}</p>
                                    <p><strong>Ваш сайт:</strong> @if($seller->link)
                                       <a href="{{ $seller->link }}" target="_blank">{{ $seller->link }}</a>
                                    @endif</p>
                                 </div>
                              </div>
                           </div>



                        </div>
                        <div class="col-lg-4 mb-20">


                           <div class="card card-default card-md bg- ">
                              <div class="card-header">
                                 <h6>Тариф</h6>

                              </div>
                              <div class="card-body">
                                 <div class="card-content">
                                    <p><strong>Телефон:</strong>@if($seller->phone){{ $seller->phone }} @else {{ 'Не указан' }}@endif</p>
                                    <p><strong>Количество товаров:</strong> {{ $seller->product_count }}</p>
                                    <p><strong>Баланс:</strong> {{ $seller->balance }} руб.</p>

                                    <p><strong>Прием заказов:</strong>  
                                       @if($seller->can_order)
                                          <span class="badge badge-round badge-success badge-lg">Доступно</span>
                                       @else
                                          <span class="badge badge-round badge-danger badge-lg">Недоступно</span>
                                       @endif
                                    </p>
                                 </div>
                              </div>
                           </div>



                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <!-- ends: .card -->
         </div>
         <!-- ends: .col-12 -->

         @endif
      </div>
   </div>

</div>
@endsection
