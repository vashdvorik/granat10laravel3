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
  </head>
<body class="w-screen relative overflow-x-hidden min-h-screen bg-gray-100 scrollbar-hide authentication-sign-in-page dark:bg-[#000]">
    <div class="wrapper mx-auto text-gray-900 font-normal grid scrollbar-hide grid-cols-[257px,1fr] grid-rows-[auto,1fr]" id="layout">

    <header class="flex items-center justify-between flex-wrap bg-neutral-bg p-5 gap-5 md:py-6 md:pl-[25px] md:pr-[38px] lg:flex-nowrap dark:bg-dark-neutral-bg lg:gap-0">
        <a class="hidden logo" href="/">
            <img class="md:mr-[100px] lg:mr-[133px]" src="{{ asset('img/logo.png') }}" alt="логотип"></a>
        <div class="bg-gray-100 flex rounded-xl w-full m-0 py-[14px] px-[18px] xl:w-[360px] dark:bg-gray-dark-100 lg:max-w-[250px] xl:max-w-[360px] lg:mr-[47px] lg:ml-6 order-last lg:order-first">
            <img src="{{ asset('auth/assets/images/icons/icon-search-normal.svg') }}" alt="seacrh icon">
          <input class="input w-full bg-transparent outline-none pl-2 h-5 text-gray-300 focus:!outline-none placeholder:text-gray-900 dark:placeholder:text-gray-dark-300 placeholder:font-semibold" type="text" placeholder="Я ищу ...">
    <!--      <img src="{{ asset('auth/assets/images/icons/icon-microphone-2.svg') }}" alt="microphone icon"> -->
        </div>
    </header>

    <main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    @yield('content') 
        
    
    
    <footer class="mt-[37px]">
      <div class="w-full bg-neutral h-[1px] dark:bg-dark-neutral-border mb-[25px]"></div>
      <div class="flex items-center justify-between text-desc text-gray-400 flex-wrap gap-5 dark:text-gray-dark-400">
        <div class="flex items-center gap-2 flex-wrap">
          <p> <span>© 2023 -</span><span class="text-color-brands">&nbsp;Frox</span><span>&nbsp;Dashboard</span></p>
          <div class="bg-color-brands rounded-full hidden w-[2px] h-[2px] md:block"></div>
          <p> <span>Made by</span><a class="text-color-brands" href="https://alithemes.com" target="_blank">&nbsp;AliThemes</a></p>
        </div>
        <div class="flex items-center gap-[15px]"><a class="transition-colors duration-300 hover:text-color-brands" href="#">About</a><a class="transition-colors duration-300 hover:text-color-brands" href="#">Careers</a><a class="transition-colors duration-300 hover:text-color-brands" href="#">Policy</a><a class="transition-colors duration-300 hover:text-color-brands" href="#">Contact</a></div>
      </div>
    </footer>
    </main>

    </div>
    <script type="text/javascript" src="{{ asset('auth/assets/scripts/vendors/jquery-3.6.0.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('auth/assets/scripts/chart-utils.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('auth/assets/scripts/chart.min.js') }}"></script>
    <script type="text/javascript" src="https://unpkg.com/chartjs-chart-geo@3"></script>
    <script src="{{ asset('auth/assets/scripts/app.js?v=5.0') }}"></script>
  </body>
</html>
