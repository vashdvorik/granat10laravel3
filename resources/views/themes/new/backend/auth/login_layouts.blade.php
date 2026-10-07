<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="{{ asset('img/favicon.png') }}?v={{ filemtime(public_path('img/favicon.png')) }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('auth/assets/styles/tailwind.min.css?v=5.0') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-new.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body.bimm-auth{display:block;min-height:100vh;color:#222;background:linear-gradient(135deg,#fff5ef 0%,#fff 48%,#fff1e9 100%)}
        .bimm-auth__header{display:flex;width:100%;align-items:center;justify-content:space-between;padding:20px max(20px,5vw);border-bottom:1px solid #f0e1d8;background:#fff}
        .bimm-auth__main{display:grid;width:100%;border:0;border-radius:0;box-shadow:none;background:transparent;place-items:center;min-height:calc(100vh - 83px);padding:28px 20px}
        .bimm-auth__main form{width:min(100%,460px);margin:20px auto!important;padding:34px!important;border:1px solid #f0dfd6;border-radius:12px;background:#fff!important;box-shadow:0 12px 35px rgba(170,70,20,.1);text-align:left!important}
        .bimm-auth__main form h3{margin-bottom:8px;color:#222;font-family:Inter,system-ui,sans-serif;font-size:28px;letter-spacing:-.04em}
        .bimm-auth__main form input{border-color:#dedede;border-radius:7px}
        .bimm-auth__main form input:focus{border-color:#ff5a1f;box-shadow:0 0 0 3px rgba(255,90,31,.13)}
        .bimm-auth__main form button{border-radius:7px;background:#ff5a1f!important}
        @media(max-width:600px){.bimm-auth__header{padding:16px 15px}.bimm-auth__main{padding:10px}.bimm-auth__main form{padding:23px!important}}
    </style>
</head>
<body class="bimm-auth theme-new">
    <header class="bimm-auth__header"><a class="bimm-brand" href="{{ route('home') }}"><span class="bimm-brand__mark" aria-hidden="true"></span><span>BIMM</span></a><a class="bimm-button bimm-button--light" href="{{ route('home') }}">Вернуться на сайт</a></header>
    <main class="bimm-auth__main">@yield('content')</main>
</body>
</html>
