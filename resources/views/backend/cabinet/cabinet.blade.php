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
                  <div class="col-lg-12">

                     <div class="breadcrumb-main">
                        <h4 class="text-capitalize breadcrumb-title">Добро пожаловать!</h4>
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
                  <div class="col-lg-12">
                     <div class="card card-default card-md mb-4">
                        <div class="card-header">
                           <h6>Создание магазина</h6>
                        </div>
                        <div class="card-body">


                           <div class="dm-empty text-center">
                              <div class="dm-empty__text">

                                 <p>Чтобы начать нажмите создать магазин. Нажимая создать магазин, вы соглашаетесь с условиями пользования.<a href="#">Читать условия</a></p>
                                 <div class="dm-empty__action">
                                 <a href="{{ route('cabinet.create_shop') }}" class="btn btn-primary btn-sm btn-squared">
                                    Создать магазин
                                 </a>
                                 </div>

                              </div>
                           </div>


                        </div>
                     </div>
                  </div>
                  @else 
         
            <div class="col-lg-12">

               <div class="breadcrumb-main">
                  <h4 class="text-capitalize breadcrumb-title">Магазин "seller1"</h4>
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
                                 <h1>0</h1>
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
                                 <h1>300 руб.</h1>
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

               <div class="card border-0 px-25 browser-states">
                  <div class="card-header px-0 border-0">
                     <h6>Пакет "Start"</h6>
                     <div class="card-extra">
                        <ul class="card-tab-links nav-tabs nav" role="tablist">
                           <li>
                              <a class="active" href="#t_selling-today2" data-bs-toggle="tab" id="t_selling-today2-tab" role="tab" aria-selected="true">Сменить пакет</a>
                           </li>
                        </ul>
                     </div>
                  </div>
                  <div class="card-body p-0">
                     <div class="tab-content">
                        <div class="tab-pane fade active show" id="t_selling-today2" role="tabpanel" aria-labelledby="t_selling-today2-tab">
                           <div class="selling-table-wrap">
                              <div class="table-responsive">
                                 <table class="table table--default table-borderless">
                                    <thead>
                                       <tr>
                                          <th>Пакет </th>
                                          <th>Session</th>
                                          <th>Bounce Rate</th>
                                          <th>CTE</th>
                                          <th>Goal Conv. Rate</th>
                                       </tr>
                                    </thead>
                                    <tbody>
                                       <tr>
                                          <td>
                                             <div class="selling-product-img d-flex align-items-center">
                                                <span>Google Chrome</span>
                                             </div>
                                          </td>
                                          <td>92,345</td>
                                          <td>3.5%</td>
                                          <td>
                                             12025
                                          </td>
                                          <td>90%</td>
                                       </tr>
                                       <tr>
                                          <td>
                                             <div class="selling-product-img d-flex align-items-center">
                                                <span>Mozila Firefox</span>
                                             </div>
                                          </td>
                                          <td>92,345</td>
                                          <td>3.5%</td>
                                          <td>
                                             12025
                                          </td>
                                          <td>90%</td>
                                       </tr>
                                       <tr>
                                          <td>
                                             <div class="selling-product-img d-flex align-items-center">
                                                <span>Apple Safari</span>
                                             </div>
                                          </td>
                                          <td>92,345</td>
                                          <td>3.5%</td>
                                          <td>
                                             12025
                                          </td>
                                          <td>90%</td>
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
         @endif
      </div>
   </div>

</div>
@endsection
