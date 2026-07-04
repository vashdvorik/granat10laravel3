<!doctype html>
<html lang="ru" dir="ltr">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <title>Кабинет продавца | Маркетплейс Гранат</title>

   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap&subset=cyrillic" rel="stylesheet">

   <!-- inject:css-->
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/bootstrap/bootstrap.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/fontawesome.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/footable.standalone.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/fullcalendar@5.2.0.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/jquery-jvectormap-2.0.5.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/jquery.mCustomScrollbar.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/leaflet.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/line-awesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/magnific-popup.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/MarkerCluster.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/MarkerCluster.Default.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/slick.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/star-rating-svg.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/trumbowyg.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/vendor_assets/css/wickedpicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('cabinet/assets/style.css') }}">
<link rel="stylesheet" href="{{ asset('css/seller-cabinet.css') }}?v={{ filemtime(public_path('css/seller-cabinet.css')) }}">
<style>
   body.side-menu .btn,
   body.side-menu button,
   body.side-menu .dropdown-wrapper__more,
   body.side-menu .product-moderation-actions,
   body.side-menu .product-moderation-actions *,
   body.side-menu .text-capitalize {
      text-transform: none !important;
   }

   .product-moderation-issues {
      background: #fff7ec;
      border: 1px solid #ffe1bd;
      border-radius: 6px;
      color: #5f3b00;
      padding: 16px 18px;
   }

   .product-moderation-issues__title {
      color: #ff7a00;
      font-weight: 700;
      line-height: 1.35;
      margin-bottom: 12px;
   }

   .product-moderation-issues__list {
      display: flex;
      flex-direction: column;
      gap: 12px;
   }

   .product-moderation-issues__item {
      background: rgba(255, 255, 255, 0.55);
      border-radius: 6px;
      padding: 12px;
   }

   .product-moderation-issues__field {
      font-weight: 700;
      margin-bottom: 6px;
   }

   .product-moderation-issues__image-index {
      color: #8a5a00;
      display: inline-block;
      font-size: 13px;
      font-weight: 600;
      margin-left: 8px;
   }

   .product-moderation-issues__field a {
      color: #7a2cff;
      text-decoration: none;
   }

   .product-moderation-issues__field a:hover {
      text-decoration: underline;
   }

   .product-moderation-issues__body {
      align-items: flex-start;
      display: flex;
      gap: 12px;
   }

   .product-moderation-issues__thumb {
      border: 1px solid #ffe1bd;
      border-radius: 6px;
      display: block;
      flex: 0 0 86px;
      overflow: hidden;
      width: 86px;
   }

   .product-moderation-issues__thumb img {
      aspect-ratio: 4 / 3;
      display: block;
      height: auto;
      object-fit: cover;
      width: 100%;
   }

   .product-moderation-issues__content {
      flex: 1 1 auto;
      min-width: 0;
   }

   .product-moderation-issues__message,
   .product-moderation-issues__suggestion {
      color: #ff7a00;
      line-height: 1.55;
      overflow-wrap: anywhere;
   }

   .product-moderation-issues__suggestion {
      margin-top: 6px;
   }

   .product-moderation-issues__suggestion span {
      font-weight: 700;
   }

   .product-form-page .product-form-page__container {
      padding-left: 24px;
      padding-right: 24px;
   }

   .product-form-page .product-form-shell {
      background: transparent !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      margin-bottom: 32px !important;
      padding: 0 !important;
   }

   .product-form-page .product-form-column {
      flex: 0 0 100%;
      max-width: 100%;
      width: 100%;
   }

   .product-form-page .product-form-inner {
      margin-left: 0 !important;
      margin-right: 0 !important;
   }

   .product-form-page .product-form-section {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      box-shadow: none;
      padding: 0 !important;
   }

   .product-form-page .product-form-section > .card-body {
      border-radius: inherit;
      box-shadow: none;
   }

   .product-form-page .product-form-section > .card-body > .card-header {
      border-bottom: 1px solid #edf0f3;
      min-height: auto;
      padding: 20px 24px;
   }

   .product-form-page .product-form-alerts,
   .product-form-page .add-product__body,
   .product-form-page .add-product__body-img {
      padding-left: 24px !important;
      padding-right: 24px !important;
   }

   .product-form-page .add-product__body,
   .product-form-page .add-product__body-img {
      padding-bottom: 28px;
      padding-top: 24px;
   }

   .product-form-page .product-form-actions {
      align-items: center;
      background: rgba(255, 255, 255, 0.96);
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      bottom: 12px;
      box-shadow: 0 8px 24px rgba(39, 43, 65, 0.1);
      margin-top: 20px !important;
      padding: 12px;
      position: sticky;
      z-index: 20;
   }

   .cabinet-marketplace-top-link {
      align-items: center;
      background: #fff7ec;
      border: 1px solid #ffd8a8;
      border-radius: 8px;
      color: #5f3b00;
      display: inline-flex;
      font-size: 14px;
      font-weight: 600;
      gap: 8px;
      line-height: 1.2;
      padding: 10px 14px;
      text-decoration: none;
   }

   .cabinet-marketplace-top-link:hover {
      color: #ff7a00;
   }

   .sidebar_nav .marketplace-return-link {
      background: #fff7ec;
      color: #5f3b00;
      font-weight: 600;
   }

   @media (max-width: 575.98px) {
      .product-moderation-issues__body {
         flex-direction: column;
      }

      .product-moderation-issues__thumb {
         width: 120px;
      }

      .product-form-page .product-form-page__container {
         padding-left: 12px;
         padding-right: 12px;
      }

      .product-form-page .breadcrumb-main {
         align-items: flex-start;
         gap: 12px;
         padding: 18px 0;
      }

      .product-form-page .product-form-section {
         border-left: 0;
         border-radius: 0;
         border-right: 0;
         margin-left: -12px;
         margin-right: -12px;
      }

      .product-form-page .product-form-section > .card-body > .card-header {
         padding: 16px;
      }

      .product-form-page .product-form-alerts,
      .product-form-page .add-product__body,
      .product-form-page .add-product__body-img {
         padding-left: 16px !important;
         padding-right: 16px !important;
      }

      .product-form-page .add-product__body,
      .product-form-page .add-product__body-img {
         padding-bottom: 20px;
         padding-top: 18px;
      }

      .product-form-page .product-form-actions {
         bottom: 8px;
         display: grid !important;
         grid-template-columns: 1fr;
         margin-left: -4px;
         margin-right: -4px;
         padding: 8px;
      }

      .product-form-page .product-form-actions .btn {
         justify-content: center;
         margin: 0;
         width: 100%;
      }

      .cabinet-marketplace-top-link {
         font-size: 12px;
         padding: 8px 10px;
      }
   }
</style>

   <!-- endinject -->

   <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicon.png') }}">

   <!-- Fonts -->
   <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
   @stack('styles')

</head>

<body class="layout-light side-menu seller-cabinet-body">
   <div class="mobile-search">
      <form action="/" class="search-form">
         <img src="{{ asset('cabinet/img/svg/search.svg') }}" alt="search" class="svg">
         <input class="form-control me-sm-2 box-shadow-none" type="search" placeholder="Search..." aria-label="Search">
      </form>
   </div>
   <div class="mobile-author-actions"></div>
   
   <header class="header-top">
      <nav class="navbar navbar-light">
         <div class="navbar-left">
            <div class="logo-area">
               <a class="navbar-brand" href="{{ route('account.index') }}">
                  <img class="dark" src="{{ asset('img/logo.png') }}" alt="logo">
                  <img class="light" src="{{ asset('img/logo.png') }}" alt="logo">
               </a>
               <a href="#" class="sidebar-toggle">
                  <img class="svg" src="{{ asset('cabinet/img/svg/align-center-alt.svg') }}" alt="img"></a>
            </div>
         </div>
         <a href="{{ url('/') }}" class="cabinet-marketplace-top-link">
            <span class="uil uil-store"></span>
            Вернуться в маркетплейс
         </a>
         <div class="navbar-right">
            <ul class="navbar-right__menu">
               @php
                  $cabinetNotifications = auth()->user()->notifications()->latest()->limit(5)->get();
                  $unreadNotificationsCount = auth()->user()->unreadNotifications()->count();
               @endphp
               <li class="nav-notification">
                  <div class="dropdown-custom">
                     <a href="javascript:;" class="nav-item-toggle {{ $unreadNotificationsCount > 0 ? 'icon-active' : '' }}">
                        <img class="svg" src="{{ asset('cabinet/img/svg/alarm.svg') }}" alt="Уведомления">
                     </a>
                     <div class="dropdown-parent-wrapper">
                        <div class="dropdown-wrapper">
                           <h2 class="dropdown-wrapper__title">
                              Уведомления
                              @if($unreadNotificationsCount > 0)
                                 <span class="badge-circle badge-warning ms-1">{{ $unreadNotificationsCount }}</span>
                              @endif
                           </h2>
                           <ul>
                              @forelse($cabinetNotifications as $notification)
                                 <li class="nav-notification__single {{ $notification->read_at ? '' : 'nav-notification__single--unread' }} d-flex flex-wrap">
                                    <div class="nav-notification__type nav-notification__type--primary">
                                       <img class="svg" src="{{ asset('cabinet/img/svg/inbox.svg') }}" alt="">
                                    </div>
                                    <div class="nav-notification__details">
                                       <p>
                                          <a href="{{ route('account.notifications.read', $notification->id) }}" class="subject stretched-link">
                                             {{ $notification->data['subject'] ?? $notification->data['product_name'] ?? 'Уведомление' }}
                                          </a>
                                          <span>{{ $notification->data['title'] ?? $notification->data['status_label'] ?? 'Статус изменён' }}</span>
                                       </p>
                                       @if(!empty($notification->data['status_label']) && !empty($notification->data['title']))
                                          <p>{{ $notification->data['status_label'] }}</p>
                                       @endif
                                       @if(!empty($notification->data['comment']))
                                          <p>{{ \Illuminate\Support\Str::limit($notification->data['comment'], 80) }}</p>
                                       @endif
                                       <p><span class="time-posted">{{ $notification->created_at->diffForHumans() }}</span></p>
                                    </div>
                                 </li>
                              @empty
                                 <li class="p-3 text-center text-muted">Новых уведомлений нет</li>
                              @endforelse
                           </ul>
                           @if($unreadNotificationsCount > 0)
                              <form method="POST" action="{{ route('account.notifications.read-all') }}">
                                 @csrf
                                 <button type="submit" class="dropdown-wrapper__more border-0 bg-transparent w-100">
                                    Отметить все прочитанными
                                 </button>
                              </form>
                           @endif
                        </div>
                     </div>
                  </div>
               </li>
               <!--
               <li class="nav-notification">
                  <div class="dropdown-custom">
                     <a href="javascript:;" class="nav-item-toggle icon-active">
                        <img class="svg" src="{{ asset('cabinet/img/svg/alarm.svg') }}" alt="img">
                     </a>
                     <div class="dropdown-parent-wrapper">
                        <div class="dropdown-wrapper">
                           <h2 class="dropdown-wrapper__title">Notifications <span class="badge-circle badge-warning ms-1">4</span></h2>
                           <ul>
                              <li class="nav-notification__single nav-notification__single--unread d-flex flex-wrap">
                                 <div class="nav-notification__type nav-notification__type--primary">
                                    <img class="svg" src="{{ asset('cabinet/img/svg/inbox.svg') }}" alt="inbox">
                                 </div>
                                 <div class="nav-notification__details">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">James</a>
                                       <span>sent you a message</span>
                                    </p>
                                    <p>
                                       <span class="time-posted">5 hours ago</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="nav-notification__single nav-notification__single--unread d-flex flex-wrap">
                                 <div class="nav-notification__type nav-notification__type--secondary">
                                    <img class="svg" src="{{ asset('cabinet/img/svg/upload.svg') }}" alt="upload">
                                 </div>
                                 <div class="nav-notification__details">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">James</a>
                                       <span>sent you a message</span>
                                    </p>
                                    <p>
                                       <span class="time-posted">5 hours ago</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="nav-notification__single nav-notification__single--unread d-flex flex-wrap">
                                 <div class="nav-notification__type nav-notification__type--success">
                                    <img class="svg" src="{{ asset('cabinet/img/svg/log-in.svg') }}" alt="log-in">
                                 </div>
                                 <div class="nav-notification__details">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">James</a>
                                       <span>sent you a message</span>
                                    </p>
                                    <p>
                                       <span class="time-posted">5 hours ago</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="nav-notification__single nav-notification__single d-flex flex-wrap">
                                 <div class="nav-notification__type nav-notification__type--info">
                                    <img class="svg" src="{{ asset('cabinet/img/svg/at-sign.svg') }}" alt="at-sign">
                                 </div>
                                 <div class="nav-notification__details">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">James</a>
                                       <span>sent you a message</span>
                                    </p>
                                    <p>
                                       <span class="time-posted">5 hours ago</span>
                                    </p>
                                 </div>
                              </li>
                              <li class="nav-notification__single nav-notification__single d-flex flex-wrap">
                                 <div class="nav-notification__type nav-notification__type--danger">
                                    <img src="{{ asset('cabinet/img/svg/heart.svg') }}" alt="heart" class="svg">
                                 </div>
                                 <div class="nav-notification__details">
                                    <p>
                                       <a href="" class="subject stretched-link text-truncate" style="max-width: 180px;">James</a>
                                       <span>sent you a message</span>
                                    </p>
                                    <p>
                                       <span class="time-posted">5 hours ago</span>
                                    </p>
                                 </div>
                              </li>
                           </ul>
                           <a href="" class="dropdown-wrapper__more">See all incoming activity</a>
                        </div>
                     </div>
                  </div>
               </li>-->

               <!-- ends: .nav-notification 
               <li class="nav-settings">
                  <div class="dropdown-custom">
                     <a href="javascript:;" class="nav-item-toggle">
                        <img src="{{ asset('cabinet/img/setting.png') }}" alt="setting">
                     </a>
                     <div class="dropdown-parent-wrapper">
                        <div class="dropdown-wrapper dropdown-wrapper--large">
                           <ul class="list-settings">
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/mail.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">All Features</a>
                                    </h6>
                                    <p>Introducing Increment subscriptions </p>
                                 </div>
                              </li>
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/color-palette.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">Themes</a>
                                    </h6>
                                    <p>Third party themes that are compatible</p>
                                 </div>
                              </li>
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/home.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">Payments</a>
                                    </h6>
                                    <p>We handle billions of dollars</p>
                                 </div>
                              </li>
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/video-camera.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">Design Mockups</a>
                                    </h6>
                                    <p>Share planning visuals with clients</p>
                                 </div>
                              </li>
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/document.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">Content Planner</a>
                                    </h6>
                                    <p>Centralize content gethering and editing</p>
                                 </div>
                              </li>
                              <li class="d-flex">
                                 <div class="me-3"><img src="{{ asset('cabinet/img/microphone.png') }}" alt=""></div>
                                 <div class="flex-grow-1">
                                    <h6>
                                       <a href="" class="stretched-link">Diagram Maker</a>
                                    </h6>
                                    <p>Plan user flows & test scenarios</p>
                                 </div>
                              </li>
                           </ul>
                        </div>
                     </div>
                  </div>
               </li>-->
               <!-- ends: .nav-support 
               <li class="nav-author">
                  <div class="dropdown-custom">
                     <a href="javascript:;" class="nav-item-toggle"><img src="{{ asset('cabinet/img/author-nav.jpg') }}" alt="" class="rounded-circle">
                        <span class="nav-item__title">Даниил<i class="las la-angle-down nav-item__arrow"></i></span>
                     </a>
                     <div class="dropdown-parent-wrapper">
                        <div class="dropdown-wrapper">
                           <div class="nav-author__info">
                              <div class="author-img">
                                 <img src="{{ asset('cabinet/img/author-nav.jpg') }}" alt="" class="rounded-circle">
                              </div>
                              <div>
                                 <h6>Rabbi Islam Rony</h6>
                                 <span>UI Designer</span>
                              </div>
                           </div>
                           <div class="nav-author__options">
                              <ul>
                                 <li>
                                    <a href="">
                                       <i class="uil uil-bag"></i> Магазин</a>
                                 </li>
                                 <li>
                                    <a href="">
                                       <i class="uil uil-setting"></i>
                                       Настройка</a>
                                 </li>
                                 <li>
                                    <a href="">
                                       <i class="uil uil-bill"></i> Финансы</a>
                                 </li>
                                 <li>
                                    {{--
                                    <a href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                       <i class="uil uil-sign-out-alt"></i> Выйти</a>
                                       <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                          @csrf
                                       </form>
                                    --}}
                                    <a href="{{ route('logout') }}">
                                       <span class="nav-icon uil uil-sign-out-alt"></span>
                                       <span class="menu-text">Выйти</span>
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                                       @csrf
                                    </form>
                                 </li>
                              </ul>
                           </div>
                        </div>
                        
                     </div>
                  </div>
               </li>-->
               <!-- ends: .nav-author -->
            </ul>
            <!-- ends: .navbar-right__menu 
            <div class="navbar-right__mobileAction d-md-none">
               <a href="#" class="btn-author-action">
                  <img class="svg" src="{{ asset('cabinet/img/svg/more-vertical.svg') }}" alt="more-vertical"></a>
            </div>-->
         </div>
         <!-- ends: .navbar-right -->
      </nav>
   </header>
   <main class="main-content">

      <div class="sidebar-wrapper">
         
         <div class="sidebar sidebar-collapse" id="sidebar">
            <div class="sidebar__menu-group">
               <ul class="sidebar_nav">
                  <!--магазин-->
                  <li class="has-child {{ request()->routeIs('account.store.*') || request()->routeIs('account.packages.*') || request()->routeIs('cabinet.create_shop') ? 'open active' : '' }}">
                     <a href="{{ route('account.store.index') }}" class="">
                        <span class="nav-icon uil uil-bag"></span>
                        <span class="menu-text text-initial">Магазин</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        @if(auth()->check() && !auth()->user()->seller)
                           <li class="{{ request()->routeIs('account.store.create') ? 'active' : '' }}">
                              <a href="{{ route('account.store.create') }}">Создать магазин</a>
                           </li>
                        @endif
                        
                        @if(auth()->check() && auth()->user()->seller)
                        <li class="{{ request()->routeIs('account.store.index') ? 'active' : '' }}">
                           <a href="{{ route('account.store.index') }}">Общая информация</a>
                        </li>
                        <li class="{{ request()->routeIs('account.store.products.index') ? 'active' : '' }}">
                           <a href="{{ route('account.store.products.index') }}">Каталог товаров</a>
                        </li>
                        <li class="{{ request()->routeIs('account.store.products.create') ? 'active' : '' }}">
                           <a href="{{ route('account.store.products.create') }}">Добавить товар</a>
                        </li>
                        <li class="{{ request()->routeIs('account.store.orders.*') ? 'active' : '' }}">
                           <a href="{{ route('account.store.orders.index') }}">Заказы</a>
                        </li>
                        @if(auth()->check() && auth()->user()->seller)
                        <li class="{{ request()->routeIs('account.packages.index') ? 'active' : '' }}">
                           <a href="{{ route('account.packages.index') }}">Тарифы</a>
                        </li>
                        @endif
                        <li class="{{ request()->routeIs('account.store.delete') ? 'active' : '' }}">
                           <a href="{{ route('account.store.delete') }}">Удалить магазин</a>
                        </li>
                        @endif  

              
                     </ul>
                  </li>
                  <!--аккаунт-->
                  <li class="has-child {{ request()->routeIs('account.user.*') ? 'open active' : '' }}">
                     <a href="{{ route('account.user.index') }}" class="">
                        <span class="nav-icon uil uil-users-alt"></span>
                        <span class="menu-text">Аккаунт</span>
                        <span class="toggle-icon"></span>
                     </a>
                     <ul>
                        <li class="{{ request()->routeIs('account.user.index') ? 'active' : '' }}">
                              <a href="{{ route('account.user.index') }}">Профиль</a>
                        </li>
                        
                        <li class="{{ request()->routeIs('account.user.change_password') ? 'active' : '' }}">
                           <a href="{{ route('account.user.change_password') }}">Сменить пароль</a>
                        </li>

                     </ul>
                  </li>
                  <!--статистика-->
                  @if(auth()->check() && auth()->user()->seller)
                  <li class="{{ request()->routeIs('account.stats') ? 'active' : '' }}">
                     <a href="{{ route('account.stats') }}">
                        <span class="nav-icon uil uil-chart-bar"></span>
                        <span class="menu-text">Статистика</span>
                     </a>
                  </li>
                  @endif
                  <li class="">
                     <a href="{{ url('/') }}" class="marketplace-return-link">
                        <span class="nav-icon uil uil-signin"></span>
                        <span class="menu-text">Вернуться в маркетплейс</span>
                     </a>
                  </li>
                  <!--выйти-->
                  <li class="">
                  <a href="{{ route('logout') }}">
                     <span class="nav-icon uil uil-sign-out-alt"></span>
                     <span class="menu-text">Выйти из аккаунта</span>
                  </a>
                  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                     @csrf
                  </form>

                  </li>

               </ul>
            </div>
         </div>
      </div>

   @yield('content_cabinet')

      <footer class="footer-wrapper">
         <div class="footer-wrapper__inside">
            <div class="container-fluid">
               <div class="row">
                  <div class="col-md-6">
                     <div class="footer-copyright">
                        <p><span>Техническая поддержка</span><a href="tel:077993603"> (779)93-603</a>
                        </p>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="footer-menu text-end">
                        <ul>
                           <li>
                              <a href="#">About</a>
                           </li>
                           <li>
                              <a href="#">Team</a>
                           </li>
                           <li>
                              <a href="#">Contact</a>
                           </li>
                        </ul>
                     </div>
                     <!-- ends: .Footer Menu -->
                  </div>
               </div>
            </div>
         </div>
      </footer>

   </main>
   <div id="overlayer">
      <div class="loader-overlay">
         <div class="dm-spin-dots spin-lg">
            <span class="spin-dot badge-dot dot-primary"></span>
            <span class="spin-dot badge-dot dot-primary"></span>
            <span class="spin-dot badge-dot dot-primary"></span>
            <span class="spin-dot badge-dot dot-primary"></span>
         </div>
      </div>
   </div>
   <div class="overlay-dark-sidebar"></div>
   <div class="customizer-overlay"></div>
 <!--  <script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyBgYKHZB_QKKLWfIRaYPCadza3nhTAbv7c"></script>-->
   <!-- inject:js-->

<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery/jquery-3.5.1.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery/jquery-ui.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/bootstrap/popper.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/moment/moment.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/accordion.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/apexcharts.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/autoComplete.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/Chart.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/daterangepicker.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/drawer.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/dynamicBadge.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/dynamicCheckbox.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/footable.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/fullcalendar@5.2.0.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/google-chart.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery-jvectormap-2.0.5.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery-jvectormap-world-mill-en.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery.countdown.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery.filterizr.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery.peity.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/jquery.star-rating-svg.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/leaflet.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/leaflet.markercluster.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/loader.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/message.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/moment.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/muuri.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/notification.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/popover.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/select2.full.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/slick.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/trumbowyg.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/vendor_assets/js/wickedpicker.min.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/apexmain.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/charts.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/drag-drop.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/footable.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/full-calendar.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/googlemap-init.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/icon-loader.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/jvectormap-init.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/leaflet-init.js') }}"></script>
<script src="{{ asset('cabinet/assets/theme_assets/js/main.js') }}"></script>
<script>
   (function() {
      function syncSellerSidebarState() {
         var sidebar = document.querySelector('.seller-cabinet-body .sidebar');
         document.body.classList.toggle('seller-sidebar-collapsed', !!sidebar && sidebar.classList.contains('collapsed'));
      }

      function bootSellerSidebarState() {
         var sidebar = document.querySelector('.seller-cabinet-body .sidebar');
         var sidebarToggle = document.querySelector('.seller-cabinet-body .sidebar-toggle');
         var sidebarOverlay = document.querySelector('.seller-cabinet-body .overlay-dark-sidebar');

         syncSellerSidebarState();

         if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
               window.setTimeout(syncSellerSidebarState, 0);
            });
         }

         if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
               window.setTimeout(syncSellerSidebarState, 0);
            });
         }

         window.addEventListener('resize', function() {
            window.setTimeout(syncSellerSidebarState, 0);
         });

         if (sidebar && window.MutationObserver) {
            new MutationObserver(syncSellerSidebarState).observe(sidebar, {
               attributes: true,
               attributeFilter: ['class']
            });
         }
      }

      if (document.readyState === 'loading') {
         document.addEventListener('DOMContentLoaded', bootSellerSidebarState);
      } else {
         bootSellerSidebarState();
      }
   })();
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   
<!--Подтверждение выхода из аккаунта -->
<script>
$(function() {
    $('a[href="{{ route('logout') }}"]').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Вы уверены?',
            text: 'Вы действительно хотите выйти из кабинета?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Выйти',
            cancelButtonText: 'Отмена'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#logout-form').submit();
            }
        });
    });
});
</script>
@stack('scripts')
</body>

</html>
