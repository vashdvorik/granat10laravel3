@extends('main_layots')

@section('title', 'Ответы на частые вопросы — BIMM')

@section('content')
<!--СТРАНИЦА HELP ДИНАМИЧНЫЙ КОНТЕНТ-->
<div class="container container-240">

 <!--ЧАСТЫЕ ВОПРОСЫ 1 КОЛОНКА-->   
<!--<div class="pd-banner v2">
   <a href="#" class="image-bd effect_img2"><img src="{{ asset('img/o-banner.jpg') }}" alt="" class="img-reponsive"></a> 
</div>-->
<ul class="breadcrumb">
    <li><a href="/">Главная</a></li>
    <li class="active">Помощь</li>
</ul>
<div class="e-qa v2">
    <div class="cmt-title v3 text-center abs"><h1 class="oval-bd btn-gradient">Частые вопросы</h1></div>
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="qa-element">
                <div class="qa-quest">
                    <span class="qa-icon">В</span>
                    <h3>Можно ли заказать доставку?</h3>
                </div>
                <div class="qa-answer">
                    <span class="qa-icon">О</span>
                    <p>Портал не берет на себя отвественность за доставку товаров. При оплате доставки Вы перечисляете деньги продавцу, который занимается вопросом доставки товара самостоятельно. Либо использует партнеров. </p>
                </div>
            </div>

            <div class="qa-element">
                <div class="qa-quest">
                    <span class="qa-icon">В</span>
                    <h3>На портале только продавцы г. Тирасполь?</h3>
                </div>
                <div class="qa-answer">
                    <span class="qa-icon">О</span>
                    <p>Да, только магазины находящиеся в г. Тирасполь. Также, на площадке могут размещать свои товары продавцы занимающиеся доставкой. Один из вариантов это отправка посылкой из другого города. </p>
                </div>
            </div>

            <div class="qa-element">
                <div class="qa-quest">
                    <span class="qa-icon">В</span>
                    <h3>Насколько актуальны товары на портале?</h3>
                </div>
                <div class="qa-answer">
                    <span class="qa-icon">О</span>
                    <p>Портал требует от продавцов постояного подтверждения актуальности его товаров. Если такое не происходит карточки товара теряют рейтинг и через время перестают показываться совсем. </p>
                </div>
            </div>

            <div class="qa-element">
                <div class="qa-quest">
                    <span class="qa-icon">В</span>
                    <h3>Зачем сайту моя геолокация?</h3>
                </div>
                <div class="qa-answer">
                    <span class="qa-icon">О</span>
                    <p>Алгоритм показа товара сначала показывает товары из магазинов поблизости. Либо из того региона Тирасполь, который вы выбрали. Затем те товары, которые продаются дальше от Вас. </p>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="qa-element">
                <div class="qa-quest">
                    <span class="qa-icon">В</span>
                    <h3>Можно ли заказать доставку?</h3>
                </div>
                <div class="qa-answer">
                    <span class="qa-icon">О</span>
                    <p>Портал не берет на себя отвественность за доставку товаров. При оплате доставки Вы перечисляете деньги продавцу, который занимается вопросом доставки товара самостоятельно. Либо использует партнеров. </p>
                </div>
            </div>

            <div class="qa-element">
                <div class="qa-quest">
                    <span class="qa-icon">В</span>
                    <h3>На портале только продавцы г. Тирасполь?</h3>
                </div>
                <div class="qa-answer">
                    <span class="qa-icon">О</span>
                    <p>Да, только магазины находящиеся в г. Тирасполь. Также, на площадке могут размещать свои товары продавцы занимающиеся доставкой. Один из вариантов это отправка посылкой из другого города. </p>
                </div>
            </div>

            <div class="qa-element">
                <div class="qa-quest">
                    <span class="qa-icon">В</span>
                    <h3>Насколько актуальны товары на портале?</h3>
                </div>
                <div class="qa-answer">
                    <span class="qa-icon">О</span>
                    <p>Портал требует от продавцов постояного подтверждения актуальности его товаров. Если такое не происходит карточки товара теряют рейтинг и через время перестают показываться совсем. </p>
                </div>
            </div>

            <div class="qa-element">
                <div class="qa-quest">
                    <span class="qa-icon">В</span>
                    <h3>Зачем сайту моя геолокация?</h3>
                </div>
                <div class="qa-answer">
                    <span class="qa-icon">О</span>
                    <p>Алгоритм показа товара сначала показывает товары из магазинов поблизости. Либо из того региона Тирасполь, который вы выбрали. Затем те товары, которые продаются дальше от Вас. </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!--Закрываем container-240-->
</div> 
@endsection 


