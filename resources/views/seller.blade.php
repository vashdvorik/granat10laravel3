@extends('main_layots')
@section('title', 'Продавцы Тирасполя | Маркетплейс ПМР')

@section('content')
{{--
<div class="container container-240">

    <ul class="breadcrumb v4">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li class="active">Магазины</li>
    </ul>
  
    <div class="blog spc1">
        <div class="blog-grid">
            <h1 class="blog-heading text-center">{{ $seller->name ?? 'Название продавца' }}</h1>
            <div class="row">
        
            </div>
        </div>
        <ul class="pagination">
            <li class="active"><a href="#">1</a></li>
            <li><a href="#">2</a></li>
            <li><a href="#"><i class="ion-ios-arrow-forward"></i></a></li>
        </ul>
    </div>
</div>
--}}

<div class="container container-240">
    <div class="blog-banner pd-banner v2">
        <a href="#" class="effect_img2"><img src="img/blog/blog-banner.png" alt="" class="img-reponsive"></a> 
    </div>
    <div class="blog spc1">
        <ul class="breadcrumb">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li><a href="{{ route('shops') }}">Главная</a></li>
        <li class="active">{{ $seller->name ?? 'Название продавца' }}</li>
        </ul>
        <div class="blog-single-post">
            <div class="row">
                <div class="blog-content  col-md-9  col-xs-12">
                    <div class="blog-post-item v2">
                        <div class="blog-img">
                            <a href="#" class="hover-images"><img src="{{ asset('img/blog/single-post.jpg') }}" alt="" class="img-reponsive"></a>
                            <div class="blog-post-date e-gradient abs v2">
                                <span class="b-date">{{ $seller->product_count ?? 'нет' }}</span>
                                <span class="b-month">ТОВ</span>
                            </div>
                        </div>
                        <div class="blog-info-bd">
                            <div class="blog-info">
                                <h3 class="blog-post-title v2">{{ $seller->name ?? 'Название продавца' }}</h3>
                                <div class="blog-post-desc">
                                    <p>{{ $seller->description ?? '' }}</p>
                                   
                                </div>
                            </div>
                            <div class="blog-post-author v2">
                                <div class="blog-post-tags">
                                    <a href="#">Design</a>,<a href="#">Tecnology</a>
                                </div>

                                <div class="blog-post-social">
                                    <a href="#"><i class="fa fa-twitter"></i></a>
                                    <a href="#"><i class="fa fa-twitter"></i></a>
                                    <a href="#"><i class="fa fa-twitter"></i></a>
                                    <a href="#"><i class="fa fa-twitter"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{--
                    <div class="blog-comment">
                        <div class="blog-comment-top">
                            <div class="blog-comment-item">
                                <div class="cmt-img">
                                    <img src="img/blog/author1.jpg" alt="">
                                </div>
                                <div class="cmt-content">
                                    <div class="wrp-name"><span class="name">Robert Havard</span></div>
                                    <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's<br> standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries..</p>
                                    <a href="#" class="btn-author">All author posts <i class="ion-ios-arrow-forward"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="blog-comment-content">
                            <div class="cmt-title text-center abs"><h1 class="oval-bd">Comments (3)</h1></div>
                            <ul>
                                <li>
                                    <div class="blog-comment-item">
                                        <div class="cmt-img">
                                            <img src="img/blog/author2.jpg" alt="">
                                        </div>
                                        <div class="cmt-content">
                                            <div class="wrp-name">
                                                <div class="wrp-element">
                                                    <span class="name">Robert Havard</span>
                                                    <a href="#"><i class="fa fa-reply"></i></a>
                                                </div>
                                                <span class="date">Dec 02, 2018, 6:05 pm</span>
                                            </div>
                                            <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's<br> standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries..</p>

                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="blog-comment-item">
                                        <div class="cmt-img">
                                            <img src="img/blog/author3.jpg" alt="">
                                        </div>
                                        <div class="cmt-content">
                                            <div class="wrp-name">
                                                <div class="wrp-element">
                                                    <span class="name">Robert Havard</span>
                                                    <a href="#"><i class="fa fa-reply"></i></a>
                                                </div>
                                                <span class="date">Dec 02, 2018, 6:05 pm</span>
                                            </div>
                                            <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the<br> industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries..</p>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="blog-comment-item">
                                        <div class="cmt-img">
                                            <img src="img/blog/author4.jpg" alt="">
                                        </div>
                                        <div class="cmt-content">
                                            <div class="wrp-name">
                                                <div class="wrp-element">
                                                    <span class="name">Robert Havard</span>
                                                    <a href="#"><i class="fa fa-reply"></i></a>
                                                </div>
                                                <span class="date">Dec 02, 2018, 6:05 pm</span>
                                            </div>
                                            <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's<br> standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries..</p>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="blog-comment-bottom">
                            <div class="cmt-title text-center abs"><h1 class="oval-bd">Leave a comment</h1></div>
                            <div class="cmt-form">
                                <div class="login-form">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6 col-xs-12">
                                                    <input type="text" id="author" class="form-control bdr" name="comment[author]" value="" placeholder="Name *">
                                                </div>
                                                <div class="col-md-6 col-xs-12">
                                                    <input type="email" id="email" class="form-control bdr" name="comment[email]" value="" placeholder="Name *">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <textarea id="message" class="form-control bdr2" name="comment[body]" rows="12" placeholder="Enter your comment here..."></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group text-center">
                                            <button type="submit" class="btn btn-submit btn-gradient">
                                                Send message
                                            </button>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div> --}}
                </div>
                <div class="blog-sidebar col-md-3 col-xs-12">
                    <div class="filter filter-cate">
                        <ul class="wiget-content v3">
                            <li class="active"><a href="#">Все товары</a>
                                <ul class="wiget-content v4">
                                    @foreach($categories as $category)
                                        <li>
                                            <a href="{{ route('catalog.show', $category->alias) }}">
                                            <h3>{{ $category->name }}<span class="number"> ({{ $category->total_products }})</span></h3>
                                            </a>
                                        </li>
                                {{--    @if($category->children->count() > 0)
                                            @foreach($category->children as $child)
                                                @if($child->products()->where('seller_id', $seller->id)->count() > 0)
                                                <li><a href="#">{{ $child->name }} <span class="number">({{ $child->products()->where('seller_id', $seller->id)->count() }})</span></a></li>
                                                @endif
                                            @endforeach 
                                        @endif  --}}
                                    @endforeach
                                </ul>
                    {{--       <!--    <ul class="wiget-content v4">
                                    <li><h3>Cell Phone & Accessories <span class="number">(120)</span></h3></li>
                                    <li><a href="#">Unlocked Cell Phones <span class="number">(80)</span></a></li>
                                    <li><a href="#">Carrier Phones <span class="number">(39)</span></a></li>
                                    <li><a href="#">Accessories  <span class="number">(56)</span></a></li>
                                    <li><a href="#">Wearable Technology <span class="number">(10)</span></a></li>
                                    <li><a href="#">Cases <span class="number">(30)</span></a></li>
                                    <li><a href="#">Bluetooth Headsets <span class="number">(30)</span></a></li>
                                    <li><a href="#">Top 20 Staff Pick <span class="number">(30)</span></a></li>
                                </ul> --> --}} 
                            </li>
                            
                        </ul>
                    </div>
                    {{-- виджет с категориями товаров
                    <div class="blog-widget blog-widget-category">
                        <h1 class="widget-blog-title">Категории товаров</h1>
                        <ul class="wiget-content">
                            <li><a href="#">Arts and Entertainment <span class="number">(80)</span></a></li>
                            <li><a href="#">Business <span class="number">(39)</span></a></li>
                            <li><a href="#">Technology <span class="number">(56)</span></a></li>
                            <li><a href="#">Ideas <span class="number">(10)</span></a></li>
                            <li><a href="#">House & Theater <span class="number">(30)</span></a></li>
                        </ul>
                    </div>--}}
                    <div class="blog-widget blog-widget-social">
                        <h1 class="widget-blog-title">Наши контакты</h1>
                        <div class="social">
                            <a href="#" class="fa fa-twitter"></a>
                            <a href="#" class="fa fa-dribbble"></a>
                            <a href="#" class="fa fa-behance"></a>
                            <a href="#" class="fa fa-instagram"></a>
                        </div>
                    </div>
                    {{--
                    <div class="blog-widget blog-widget-popular">
                        <h1 class="widget-blog-title">Popular post</h1>
                        <div class="owl-carousel owl-theme js-owl-post">
                            <div class="item">
                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post1.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>
                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post2.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>

                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post3.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>

                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post4.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post1.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>
                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post2.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>

                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post3.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>

                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post4.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post1.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>
                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post2.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>

                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post3.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>

                                <div class="post-item">
                                    <div class="post-img">
                                        <img src="img/blog/post4.jpg" alt="">
                                    </div>
                                    <div class="post-info">
                                        <h3><a href="#">Traveling abroad change you forever</a></h3>
                                        <p>April 30, 2016</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="blog-widget blog-widget-newsletter">
                        <h1 class="widget-blog-title">Newletter</h1>
                        <div class="newsletter">
                            <form class="form_newsletter" action="#" method="post">
                                <input type="email" value="" placeholder="Enter your emaill adress" name="EMAIL" id="mail2" class="newsletter-input form-control">
                                <button id="subscribe2" class="button_mini btn" type="submit">
                                    
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="blog-widget blog-widget-instagram">
                        <h1 class="widget-blog-title">Instagram</h1>
                        <div class="instagram">
                            <a href="#" class=""><img src="img/blog/insta_1.jpg" alt="" class="img-reponsive"></a>
                            <a href="#" class=""><img src="img/blog/insta_2.jpg" alt=""></a>
                            <a href="#" class=""><img src="img/blog/insta_3.jpg" alt=""></a>
                            <a href="#" class=""><img src="img/blog/insta_4.jpg" alt=""></a>
                            <a href="#" class=""><img src="img/blog/insta_5.jpg" alt=""></a>
                            <a href="#" class=""><img src="img/blog/insta_6.jpg" alt=""></a>
                        </div>
                    </div>
                    <div class="blog-widget-banner">
                        <a href="#" class=""><img src="img/blog/ads.png" alt="" class="img-reponsive"></a>
                    </div>
                    --}}
                </div>

            </div>
        </div>
    </div>
</div>

@endsection