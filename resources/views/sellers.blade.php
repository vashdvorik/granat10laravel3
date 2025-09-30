@extends('main_layots')
@section('title', 'Продавцы Тирасполя | Маркетплейс ПМР')

@section('content')
<div class="container container-240">

    <ul class="breadcrumb v4">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li class="active">Магазины</li>
    </ul>
    <div class="pd-banner">
        <a href="#" class="image-bd effect_img2"><img src="img/shop-banner.jpg" alt="" class="img-reponsive"></a> 
    </div>
    <div class="pd-top">
        <h1 class="title">Магазины</h1>
        <div class="show-element"><span>Showing 1–15 of 20 results</span></div>
    </div>
    <div class="pd-middle">
        <div class="pd-sort">
            <div class="filter-sort">
                <div class="dropdown">
                    <button class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <span class="dropdown-label">Выбрать район</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a href="#">Не выбрано</a></li>   
                        <li><a href="#">Зеленый рынок</a></li>
                        <li><a href="#">ул 25 октября</a></li>
                        <li><a href="#">Спутник</a></li>
                        <li><a href="#">Меркурий</a></li>
                        <li><a href="#">Самолет-тернопль</a></li>
                        <li><a href="#">Date, old to new</a></li>
                        <li><a href="#">Date, new to old</a></li>
                    </ul>
                </div>
            </div>
            <div class="filter-show">
                <div class="dropdown">
                        <button class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            Показать
                            <span class="dropdown-label">12</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="#">12</a></li>   
                            <li><a href="#">24</a></li>
                            <li><a href="#">36</a></li>
                            <li><a href="#">48</a></li>
                        </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="blog spc1">
        <div class="blog-grid">
            <h1 class="blog-heading text-center">Продавцы г. Тирасполь</h1>
            <div class="row">

            @foreach($sellers as $seller)
                <div class="col-md-4 col-sm-6 col-xs-12 blog-post-item">
                    <div class="blog-img">
                        <a href="{{ route('seller', ['alias' => $seller->alias])}}" class="hover-images">
                            <img src="{{ $seller->image ?? 'img/blog/blog_3.jpg' }}" alt="" class="img-reponsive">
                        </a>
                        <div class="blog-post-date e-gradient abs">
                            <span class="b-date">{{ $seller->product_count ?? 'нет' }}</span>
                            <span class="b-month">ТОВ</span>
                        </div>
                    </div>
                    <div class="blog-info">
                        <h3 class="blog-post-title">
                            <a href="{{ route('seller', ['alias' => $seller->alias])}}">{{ $seller->name ?? 'Название продавца' }}</a>
                        </h3>
                        <p class="blog-post-desc">{{ \Illuminate\Support\Str::limit($seller->description ?? 'Описание продавца...', 125) }}</p>
                        <div class="blog-post-author">
                            <div class="author">
                                Адрес: <span class="c-black">{{ $seller->address ?? '' }}</span>
                            </div>
                            <div class="blog-post-comment">
                                <span class="c-black"></span>{{ $seller->comments_count ?? 2 }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{--
                <div class="col-md-4 col-sm-6 col-xs-12 blog-post-item">
                    <div class="blog-img">
                        <a href="#" class="hover-images"><img src="img/blog/blog_9.jpg" alt="" class="img-reponsive"></a>
                        <div class="blog-post-date e-gradient abs">
                            <span class="b-date">14</span>
                            <span class="b-month">dec</span>
                        </div>
                    </div>
                    <div class="blog-info">
                        <h3 class="blog-post-title"><a href="#">Ricoh's latest spherical camera nets quality images</a></h3>
                        <p class="blog-post-desc">Here are many variations of passages of Lorem Ipsum availablethe majority have suffered alteration in some form...</p>
                        <div class="blog-post-author">
                            <div class="author">Posted by <span class="c-black">Admin</span></div>
                                <div class="blog-post-comment"><span class="c-black"></span>2</div>
                        </div>
                    </div>
                </div>
                --}}
            </div>
        </div>
        <ul class="pagination">
            <li class="active"><a href="#">1</a></li>
            <li><a href="#">2</a></li>
            <li><a href="#"><i class="ion-ios-arrow-forward"></i></a></li>
        </ul>
    </div>
</div>

@endsection