<!DOCTYPE html>
<html class="scroll-smooth overflow-x-hidden" lang="en">
  <head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/x-icon" sizes="16x16"> 
    <link rel="stylesheet" href="{{ asset('auth/assets/styles/tailwind.min.css?v=5.0') }}">
    <link rel="stylesheet" href="{{ asset('auth/assets/styles/style.min.css?v=5.0') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chivo:wght@400;700;900&amp;family=Noto+Sans:wght@400;500;600;700;800&amp;display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* Точные стили из main_layots.blade.php */
        .mobile-bottom-navbar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid #e0e0e0;
            z-index: 1000;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
        }

        .mobile-navbar-wrapper {
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 60px;
            max-width: 100%;
        }

        .mobile-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-decoration: none;
            color: #666;
            font-size: 12px;
            transition: color 0.3s ease, background 0.3s ease;
        }

        .mobile-nav-item:hover,
        .mobile-nav-item:active {
            color: #ff6b6b;
            background: #f5f5f5;
        }

        .mobile-nav-item i {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .mobile-nav-label {
            font-size: 11px;
            white-space: nowrap;
        }
        
        .f-18 {
            font-size: 18px;
        }
        
        .hidden-md {
            display: none;
        }
        
        .hidden-lg {
            display: none;
        }
        
        @media (min-width: 768px) {
            .hidden-md {
                display: none !important;
            }
        }
        
        @media (min-width: 992px) {
            .hidden-lg {
                display: none !important;
            }
        }
        
        @media (max-width: 767px) {
            .hidden-md.hidden-lg {
                display: block !important;
            }
            
            main {
                padding-bottom: 70px !important;
            }

            #btn-back-store {
                display: none !important;
            }
        }
        /* На мобильных устройствах логотип меньше */
        @media (max-width: 767px) {
            .logo img {
                max-height: 35px;
            }
        }

        @media (max-width: 767px) {
            header {
                min-height: 60px;
                padding: 10px 20px !important;
            }
        }

        @media (min-width: 768px) and (max-width: 1024px) {
            header {
                min-height: 70px;
            }
        }

        @media (min-width: 1025px) {
            header {
                padding-bottom: 42px !important;
            }
        }
    </style>
  </head>
<body class="w-screen relative overflow-x-hidden min-h-screen bg-gray-100 scrollbar-hide authentication-sign-in-page dark:bg-[#000]">
  <div class="wrapper mx-auto text-gray-900 font-normal grid min-h-screen scrollbar-hide grid-cols-[257px,1fr] grid-rows-[auto,1fr]" id="layout">

    <header class="flex items-center justify-between flex-wrap bg-neutral-bg p-5 gap-5 md:py-6 md:pl-[25px] md:pr-[38px] lg:flex-nowrap dark:bg-dark-neutral-bg lg:gap-0">
        <a class="logo" href="/">
            <img class="md:mr-[100px] lg:mr-[133px]" src="{{ asset('img/logo.png') }}" alt="логотип"></a>
        <a href="{{ url('/') }}" id="btn-back-store" class="btn btn-primary btn-default btn-rounded self-center">&#8592; Вернуться в магазин</a>
    </header>

    <main class="overflow-x-scroll scrollbar-hide flex flex-1 min-h-0 flex-col pt-[42px] px-[23px] pb-[28px]">
    @yield('content') 
        
    
    
    <footer class="mt-auto pt-[37px]">
      <div class="w-full bg-neutral h-[1px] dark:bg-dark-neutral-border mb-[25px] hidden md:block"></div>
      <div class="flex items-center justify-between text-desc text-gray-400 flex-wrap gap-5 dark:text-gray-dark-400 hidden md:flex">
        <div class="flex items-center gap-2 flex-wrap">
          <p> <span>© 2025 -</span><span class="text-color-brands">&nbsp;Маркетплейс</span><span>&nbsp;</span></p>
          <div class="bg-color-brands rounded-full hidden w-[2px] h-[2px] md:block"></div>
          <p> <span>Создан:</span><a class="text-color-brands" href="https://granatlab.com/" target="_blank">&nbsp; Рекламное агентство "Гранат"</a></p>
        </div>
      <!--  <div class="flex items-center gap-[15px]"><a class="transition-colors duration-300 hover:text-color-brands" href="#">Об агентстве</a><a class="transition-colors duration-300 hover:text-color-brands" href="#">Careers</a><a class="transition-colors duration-300 hover:text-color-brands" href="#">Policy</a><a class="transition-colors duration-300 hover:text-color-brands" href="#">Contact</a></div> -->
      </div>
    </footer>
    </main>

    </div>

    <!-- МОБИЛЬНОЕ МЕНЮ ВНИЗУ (Bottom Navbar) -->
    <div class="mobile-bottom-navbar hidden-md hidden-lg">
        <div class="mobile-navbar-wrapper">
            <a href="{{ route('account.index') }}" class="mobile-nav-item" title="Кабинет">
                <i class="fa fa-user f-18"></i>
                <span class="mobile-nav-label">Кабинет</span>
            </a>
            <a href="#" class="mobile-nav-item" title="Избранное">
                <i class="fa fa-heart f-18"></i>
                <span class="mobile-nav-label">Избранное</span>
            </a>
            <a href="{{ route('catalog.index') }}" class="mobile-nav-item" title="Каталог">
                <i class="fa fa-th f-18"></i>
                <span class="mobile-nav-label">Каталог</span>
            </a>
            <a href="{{ route('catalog.sale') }}" class="mobile-nav-item" title="Распродажи">
                <img src="{{ asset('img/skidka-logo.png') }}" alt="Распродажи" style="width:20px; height:20px;">
                <span class="mobile-nav-label">Распродажи</span>
            </a>
            <a href="{{ route('shops') }}" class="mobile-nav-item" title="Магазины">
                <img src="{{ asset('img/icon-ship.png') }}" alt="Магазины" style="width:20px; height:20px;">
                <span class="mobile-nav-label">Магазины</span>
            </a>
        </div>
    </div>
    <script type="text/javascript" src="{{ asset('auth/assets/scripts/vendors/jquery-3.6.0.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('auth/assets/scripts/chart-utils.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('auth/assets/scripts/chart.min.js') }}"></script>
    <script type="text/javascript" src="https://unpkg.com/chartjs-chart-geo@3"></script>
    <script src="{{ asset('auth/assets/scripts/app.js?v=5.0') }}"></script>
    <script>
        // Показываем мобильное меню только на мобильных устройствах
        if (window.innerWidth < 768) {
            document.querySelector('.mobile-bottom-navbar').style.display = 'block';
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth < 768) {
                document.querySelector('.mobile-bottom-navbar').style.display = 'block';
            } else {
                document.querySelector('.mobile-bottom-navbar').style.display = 'none';
            }
        });
    </script>
 
  </body>
</html>
