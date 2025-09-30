@extends('main_layots')

@section('title', 'Стать продавцом на маркетплейс в ПМР')

@section('content')

<div class="container container-240">
<!--    <div class="pd-banner v2">
       <a href="#" class="image-bd effect_img2"><img src="img/o-banner.jpg" alt="" class="img-reponsive"></a> 
    </div> -->
    <ul class="breadcrumb">
        <li><a href="/">Главная</a></li>
        <li class="active">Стать продавцом</li>
    </ul>
</div>
<div class="aboutus">
    <div class="container container-240">
        <div class="about-img spc2">
            <img src="img/about/about_1.jpg" alt="" class="img-responsive">
        </div>
    </div>

    <div class="about-content">
        <div class="container container-240">
            <div class="entry-inside v4 text-center">
                <img src="img/single/simple_icon.png" alt="">
                <h1 class="entry-title v2 spc">Multi-Purpose WooCommerce Theme for Electronics Store </h1>
            </div>
        </div>
        <div class="container container-240">
            <div class="row pd1">
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <h3 class="about-title spc">Our vision</h3>
                    <p class="about-desc spc">It is a long established fact that a reader will be distracted by the readable content of a page when<br> looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of<br> letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, </p>
                    <div class="about-img">
                        
                            <img src="img/about/about_2.jpg" alt="" class="img-responsive">
                        
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <h3 class="about-title spc">Our vision</h3>
                    <p class="about-desc spc">It is a long established fact that a reader will be distracted by the readable content of a page when<br> looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of<br> letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, </p>
                    <div class="about-img">
                        
                            <img src="img/about/about_3.jpg" alt="" class="img-responsive">
                        
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gradient">
            <div class="container container-240">
                <div class="row pd2">
                    <div class="col-xs-12 col-sm-4 col-md-4">
                        <div class="about-element text-center">
                            <img src="img/about/icon1.png" alt="">
                            <h3 class="about-title v2">More than 2000 + items</h3>
                            <p class="about-desc">Radable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters</p>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-4 col-md-4">
                        <div class="about-element text-center">
                            <img src="img/about/icon2.png" alt="">
                            <h3 class="about-title v2">More than 2000 + items</h3>
                            <p class="about-desc">Radable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters</p>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-4 col-md-4">
                        <div class="about-element text-center">
                            <img src="img/about/icon3.png" alt="">
                            <h3 class="about-title v2">More than 2000 + items</h3>
                            <p class="about-desc">Radable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--ПАКЕТЫ -->
        {{--
        <div class="container container-240">
            <div class="team">
                <h1 class="about-title v3 spc text-center">Выбрать тарифный план</h1>
                <div class="owl-carousel owl-theme js-owl-team">
                    <div class="team-item">
                        <div class="team-img">
                            <img src="img/about/team_1.jpg" alt="">
                        </div>
                        <div class="team-info">
                            <h3 class="team-name"><strong>Старт</strong></h3>
                            <p class="team-career">Бесплатный</p>
                            <p class="team-desc">До 25 товаров на витрине магазина. О vip товаров. </p>
                        </div>
                    </div>

                    <div class="team-item">
                        <div class="team-img">
                            <img src="img/about/team_2.jpg" alt="">
                        </div>
                        <div class="team-info">
                            <h3 class="team-name"><strong>Эконом</strong></h3>
                            <p class="team-career">250 рублей/месяц</p>
                            <p class="team-desc">До 100 товаров на витрине магазина. 1 vip товаров.</p>
                        </div>
                    </div>

                    <div class="team-item">
                        <div class="team-img">
                            <img src="img/about/team_3.jpg" alt="">
                        </div>
                        <div class="team-info">
                            <h3 class="team-name"><strong>Стандарт</strong></h3>
                            <p class="team-career">350 рублей/месяц</p>
                            <p class="team-desc">До 250 товаров на витрине магазина. 10 vip товаров.</p>
                        </div>
                    </div>

                    <div class="team-item">
                        <div class="team-img">
                            <img src="img/about/team_4.jpg" alt="">
                        </div>
                        <div class="team-info">
                            <h3 class="team-name"><strong>Топ-продавец</strong></h3>
                            <p class="team-career">950 рублей/месяц</p>
                            <p class="team-desc">До 600 товаров на витрине магазина. 100 vip товаров.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>  --}}
    </div>
</div>

@include('components.pricing-tailwind')

@endsection
    