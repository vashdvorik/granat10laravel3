@extends('main_layots')
@section('title', 'Продавцы Тирасполя | Маркетплейс ПМР')

@section('content')
<div class="container container-240">
      <div class="pd-banner">
            <a href="#" class="image-bd effect_img2"><img src="img/shop-banner.jpg" alt="" class="img-reponsive"></a> 
        </div>
    <ul class="breadcrumb v2">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li class="active">Ошибка 404</li>
    </ul>
    <div class="error-page bd-7 text-center">
        <div class="error-img">
            <img src="img/404.png" alt="">
        </div>
        <h3 class="error-title">Этой страницы не существует.</h3>
        <form method="get" class="searchform full" action="/search" role="search">
            <input type="hidden" name="type" value="product">
            <input type="text" name="q" class="form-control" placeholder="Найти товары..." value="{{ request()->input('q') }}">
            <span class="input-group-btn">
                    <button type="submit" class="button_search"><i class="ion-ios-search-strong"></i></button>
            </span>
        </form>
        <div class="vertical-divider">
            <div class="center-element">или</div>
        </div>
        <a href="#" class="btn-back btn-gradient">Вернуться на главную<i class="ion-ios-arrow-forward"></i></a>
    </div>
</div>

@endsection