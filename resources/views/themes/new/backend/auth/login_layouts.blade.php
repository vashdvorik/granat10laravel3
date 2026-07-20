<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('auth/assets/styles/tailwind.min.css?v=5.0') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-new.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body.bimm-auth{min-height:100vh;background:radial-gradient(circle at 10% 10%,#dcefe8,transparent 30%),#f7f7f3}
        .bimm-auth__header{display:flex;align-items:center;justify-content:space-between;padding:24px max(20px,5vw)}
        .bimm-auth__main{display:grid;place-items:center;min-height:calc(100vh - 105px);padding:20px}
        .bimm-auth__main form{width:min(100%,460px);margin:20px auto!important;padding:34px!important;border:1px solid #e5e7eb;border-radius:24px;background:#fff!important;box-shadow:0 22px 70px rgba(17,24,39,.1);text-align:left!important}
        .bimm-auth__main form h3{margin-bottom:8px;font-size:28px;letter-spacing:-.04em}
        .bimm-auth__main form input{border-color:#e5e7eb;border-radius:10px}
        .bimm-auth__main form button{border-radius:11px;background:#111827!important}
        @media(max-width:600px){.bimm-auth__header{padding:18px 15px}.bimm-auth__main{padding:10px}.bimm-auth__main form{padding:23px!important}}
    </style>
</head>
<body class="bimm-auth">
    <header class="bimm-auth__header"><a class="bimm-brand" href="{{ route('home') }}"><span class="bimm-brand__mark">B</span><span>BIMM</span></a><a class="bimm-button bimm-button--light" href="{{ route('home') }}">Вернуться на сайт</a></header>
    <main class="bimm-auth__main">@yield('content')</main>
</body>
</html>
