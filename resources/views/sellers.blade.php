@extends('main_layots')
@section('title', 'Продавцы Тирасполя | Маркетплейс ПМР')

@section('meta')
    {{-- SEO: Пагинация для поисковиков --}}
    @if(isset($sellers) && $sellers->hasPages())
        @if($sellers->previousPageUrl())
            <link rel="prev" href="{{ $sellers->previousPageUrl() }}" />
        @endif
        @if($sellers->nextPageUrl())
            <link rel="next" href="{{ $sellers->nextPageUrl() }}" />
        @endif
    @endif
@endsection

@section('content')
<style>
    /* Фиксированная высота для описания продавца */
    .blog-post-desc {
        min-height: 60px;
        max-height: 60px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3; /* Ограничение в 3 строки */
        -webkit-box-orient: vertical;
        line-height: 1.5;
        margin-bottom: 15px;
    }
    
    /* Одинаковая высота для всех карточек */
    .blog-post-item {
        margin-bottom: 30px;
    }
    
    .blog-post-item .blog-info {
        min-height: 180px;
    }
</style>
<div class="container container-240">
    <div id="catalog-top"></div>

    <ul class="breadcrumb v4">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li class="active">Магазины</li>
    </ul>
    <div class="pd-banner">
        <a href="#" class="image-bd effect_img2"><img src="img/shop-banner.jpg" alt="" class="img-reponsive"></a> 
    </div>
    <div class="pd-top">
        <h1 class="title">Магазины</h1>
        <div class="show-element"><span>Показано {{ $sellers->firstItem() }}–{{ $sellers->lastItem() }} из {{ $sellers->total() }} магазинов</span></div>
    </div>
    <div class="pd-middle">
        <div class="pd-sort">
            <div class="filter-sort">
                <div class="dropdown">
                    <button class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <span class="dropdown-label">Выбрать район или рынок</span>
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
                            <span class="b-date">{{ $seller->products_count ?? 0 }}</span>
                            <span class="b-month">ТОВ</span>
                        </div>
                    </div>
                    <div class="blog-info">
                        <h3 class="blog-post-title">
                            <a href="{{ route('seller', ['alias' => $seller->alias])}}">{{ $seller->name ?? 'Название продавца' }}</a>
                        </h3>
                        <p class="blog-post-desc">{{ $seller->description ?? '' }}</p>
                        <div class="blog-post-author">
                            <div class="author">
                                Адрес: <span class="c-black">{{ $seller->address ?? '' }}</span>
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
        
        {{-- ПАГИНАЦИЯ --}}
        @if($sellers->hasPages())
            {{-- Desktop пагинация --}}
            <div class="pagination-desktop hidden-xs hidden-sm">
                {{ $sellers->links('vendor.pagination.custom') }}
            </div>
            
            {{-- Mobile: кнопка "Загрузить еще" --}}
            @if($sellers->currentPage() < $sellers->lastPage())
                <div class="load-more-wrapper hidden-md hidden-lg text-center">
                    <button class="btn btn-load-more" 
                            data-url="{{ route('shops.loadMore') }}"
                            data-current-page="{{ $sellers->currentPage() }}"
                            data-last-page="{{ $sellers->lastPage() }}">
                        <i class="ion-load-c"></i> Загрузить еще
                    </button>
                </div>
            @endif
        @endif
    </div>
</div>

@endsection