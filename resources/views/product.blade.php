@extends('main_layots')
@section('title', 'Каталог товара | Маркетплейс ПМР')

@section('content')
<div class="container container-240">
        <div class="single-product-detail product-bundle product-aff">
        <ul class="breadcrumb">
            <li><a href="{{ route('home') }}">Главная</a></li>
            <li class="active"><a href="{{ route('catalog.index') }}">Каталог</a></li>

            <!--    <li class="active">{{ $category->name }}</li> -->
            @foreach($breadcrumbs as $crumb)  
            <li>
                <a href="{{ route('catalog.show', $crumb->alias) }}">{{ $crumb->name }}</a>
            </li> 
            @endforeach
            <li class="active">{{ $product->name }}</li>
        </ul>
        <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-6">
                
                <div class="flex product-img-slide">
                    
                
                    <div class="product-images">
                        <div class="main-img js-product-slider">

                            @forelse($product->images->sortBy('order') as $image)
                                <a href="#" class="hover-images effect">
                                    <img src="{{ Storage::url($image->path) }}"
                                        alt="{{ $image->alt ?? $product->name }}"
                                        class="img-reponsive"
                                        onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';">
                                </a>
                            @empty
                                <a href="#" class="hover-images effect">
                                    <img src="{{ asset('img/no-image.jpg') }}"
                                        alt="{{ $product->name }}"
                                        class="img-reponsive">
                                </a>
                            @endforelse
                    </div>
                        @php
                            $discount = null;
                            if (!is_null($product->lowprice) && $product->price > 0) {
                                $discount = round(100 - ($product->lowprice / $product->price * 100));
                            }
                        @endphp
                        @if($discount)
                            <div class="ribbon-price v2 red"><span>- {{ $discount }}% </span></div>
                        @endif
                    </div>
                    <div class="multiple-img-list-ver2 js-click-product">
                    

                        @forelse($product->images->sortBy('order') as $key => $image)
                            <div class="product-col">
                                <div class="img{{ $key === 0 ? ' active' : '' }}">
                                    <img src="{{ Storage::url($image->path) }}"
                                        alt="{{ $image->alt ?? $product->name }}"
                                        class="img-responsive"
                                        onerror="this.onerror=null;this.src='{{ asset('img/no-image.jpg') }}';">
                                </div>
                            </div>
                        @empty
                            <div class="product-col">
                                <div class="img active">
                                    <img src="{{ asset('img/no-image.jpg') }}"
                                        alt="{{ $product->name }}"
                                        class="img-responsive">
                                </div>
                            </div>
                        @endforelse

                    </div>
                </div>
                    
                
            </div>
            <div class="col-xs-12 col-sm-6 col-md-6">
                <div class="single-flex">
                    <div class="single-product-info product-info product-grid-v2 s-50">
                        <p class="product-cate">{{ $product->category->name ?? '' }}</p>
                        <div class="product-rating">
                            <span class="star star-5"></span>
                            <span class="star star-4"></span>
                            <span class="star star-3"></span>
                            <span class="star star-2"></span>
                            <span class="star star-1"></span>
                            <div class="number-rating">( 896 reviews )</div>
                        </div>
                        <h3 class="product-title"><a href="{{ route('product.show', $product->alias) }}">{{ $product->name }}</a></h3>
                        <div class="product-price">
                        <!--    <span>$1,215.00</span> -->
                            @if(!is_null($product->lowprice))
                                <span class="red">{{ $product->lowprice }} руб.</span>
                                <span class="old">{{ $product->price }} руб.</span>
                            @else
                                <span>{{ $product->price }} руб.</span>
                            @endif
                        </div>
                        <div class="availability">
                            <p class="product-inventory"> <label>Город: </label><span> {{ $product->seller->town }}</span></p>
                        </div>
                        <div class="product-brand" style="margin-bottom: 4px;">
                            <p>Продавец: {{ $product->seller->name }}</p>
                        </div>

                            @if(!empty($product->seller->address))
                                <div class="product-sku" style="margin-top: 0;">
                                    <label>Адрес:</label>
                                    <span title="Адрес продавца">
                                        {{ $product->seller->address }}
                                    </span>
                                    @if(!empty($product->seller->link_map))
                                        <a href="{{ $product->seller->link_map }}" target="_blank" rel="noopener"
                                        style="margin-left: 8px; background: #b169d6; color: #fff; padding: 4px 12px; border-radius: 4px; text-decoration: none; font-weight: 500; display: inline-block;">
                                            Показать на карте
                                        </a>
                                    @endif
                                </div>
                            @endif
                        <div class="short-desc">
                            <p class="product-desc">{{ $product->desc }}</p>
                            <ul class="desc-list">
                            @foreach([$product->features1, $product->features2, $product->features3, $product->features4] as $feature)
                                @if(!empty($feature))
                                    <li>{{ $feature }}</li>
                                @endif
                            @endforeach
                            </ul>
                        </div>

        {{--Вывод кружочков с ссылками на цвет товара
                        <!--        <div class="color-group">
                            <label>Color :</label>
                            <a href="#" class="circle black"></a>
                            <a href="#" class="circle red"></a>
                            <a href="#" class="circle gray"></a>
                        </div> --> --}}
        {{--Вывод номера телефона после описания
                 <!--   <div class="hot-line e-gradient">
                            <p></p>
                            <div class="flex align-center tele" >
                                <div class="phone-number">
                                    <a href="tel:{{ preg_replace('/\D+/', '', $product->seller->phone) }}">
                                        {{ $product->seller->phone }}
                                    </a>
                                </div>
                            </div>
                        </div>-->--}}

                        <div class="single-product-button-group">
                            <div class="e-btn cart-qtt btn-gradient">
                                <div class="e-quantity">
                                    <input type="number" step="1" min="1" max="999" name="quantity" value="1" title="Qty" class="qty input-text js-number" size="4">
                                    <div class="tc pa">
                                        <a class="js-plus quantity-right-plus"><i class="fa fa-caret-up"></i></a>
                                        <a class="js-minus quantity-left-minus"><i class="fa fa-caret-down"></i></a>
                                    </div>
                                </div>
                                <a href="#" class="btn-add-cart"> В корзину<span class="icon-bg icon-cart v2"></span></a>
                            </div>
                            <a href="#" class="e-btn btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                        {{-- кнопка толи поделиться, толи перемешать--}}
                        {{--<a href="#" class="e-btn btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a> --}}
                        </div>
                        {{--Вывод номера телефона--}}
                        <div class="hot-line e-gradient">
                        <div aria-hidden="true" style="height:2px;"></div>
                            <div class="flex align-center tele" >
                                <div class="phone-number">
                                <div aria-hidden="true" style="height:14px;"></div>
                                    <a href="tel:{{ preg_replace('/\D+/', '', $product->seller->phone) }}">
                                        {{ $product->seller->phone }}
                                    </a>
                                </div>
                            </div>
                        </div>  
                        {{--Вывод тегов--}}
                        <div class="product-tags">
                            <label></label>
                            <a href="#"></a>
                        </div>
                        <div class="product-tags">
                            <label>Tags :</label>
                            <a href="#">Fast,</a>
                            <a href="#">Gaming,</a>
                            <a href="#">Strong</a>
                        </div>
                    </div>


                    <div class="single-product-feature s-50 hidden-xs hidden-sm">
                        <div class="bd-7">
                            <div class="single-feature-box">
                                <div class="single-feature-img">
                                    <img src="{{ asset('img/feature/credit-card2.png') }}" alt="">
                                </div>
                                <div class="single-feature-info">
                                    <h3>Оплата продавцу</h3>
                                    <p>Маркетплейс не принимает оплату.</p>
                                </div>
                            </div>

                            <div class="single-feature-box">
                                <div class="single-feature-img">
                                    <img src="{{ asset('img/feature/safety2.png') }}" alt="">
                                </div>
                                <div class="single-feature-info">
                                    <h3>Ответственность</h3>
                                    <p>Мы — только рекламная платформа.</p>
                                </div>
                            </div>

                            <div class="single-feature-box">
                                <div class="single-feature-img">
                                    <img src="{{ asset('img/feature/truck2.png') }}" alt="">
                                </div>
                                <div class="single-feature-info">
                                    <h3>Доставка от продавца</h3>
                                    <p>Доставку осуществляет сам продавец.</p>
                                </div>
                            </div>
                        </div>
                    <!--   <div class="hot-line e-gradient">
                            <p></p>
                            <div class="flex align-center tele">
                            <!--    <div class="phone-number" style="text-align: center;">
                                    <p>{{ $product->seller->phone }}</p>
                                </div> 
                                <div class="phone-number" style="text-align: center;">
                                    <a href="tel:{{ preg_replace('/\D+/', '', $product->seller->phone) }}">
                                        {{ $product->seller->phone }}
                                    </a>
                                </div>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
        <div class="single-product-tab bd-7">
            <div class="cmt-title text-center abs">
                <ul class="nav nav-tabs v3 text-center">
                    <li class="active"><a data-toggle="pill" href="#desc">Description</a></li>
                    <li><a data-toggle="pill" href="#info">Specification</a></li>
                    <li><a data-toggle="pill" href="#review">Reviews</a></li>
                </ul>
            </div>
        <div class="tab-content">
            <div id="desc" class="tab-pane fade in active">
                <div class="entry-content active">
                    <div class="e-text">
                        <div class="entry-inside v4 text-center">
                            <img src="img/single/simple_icon.png" alt="">
                            <h1 class="entry-title spc">Multi-Purpose WooCommerce Theme for Electronics Store </h1>
                        </div>
                        <div class="entry-inside v3">
                            
                                <div class="text-center image-bd">
                                    <img src="img/single/des_1.jpg" alt="">
                                </div>
                            
                                <div class="row entry-middle">
                                    <div class="entry-info col-xs-12 col-sm-6 col-md-6">
                                        <h3>This is demo heading</h3>
                                        <p>It is a long established fact that a reader will be distracted by the readable content page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using</p>
                                    </div>

                                    <div class="entry-info col-xs-12 col-sm-6 col-md-6">
                                        <h3>This is demo heading</h3>
                                        <p>It is a long established fact that a reader will be distracted by the readable content page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using</p>
                                    </div>
                                </div>
                                
                                <div class="text-center image-bd img-cal">
                                    <img src="img/single/des_2.jpg" alt="">
                                </div>
                        </div>
                    </div>
                    <div class="entry-button text-center abs">
                        <a href="#" class="btn-show">Show more<i class="ion-chevron-down"></i></a>
                    </div>
                </div>
            </div>
            <div id="info" class="tab-pane fade in">
                
            </div>
            <div id="review" class="tab-pane fade in ">
                
            </div>

        </div>
    </div>
    </div>
    <div class="bestseller">
        <div class="ecome-heading style5v3 spc5v3">
            <h1>Related products</h1>
            <a href="#" class="btn-show">Shop more<i class="ion-ios-arrow-forward"></i></a>
        </div>
        <div class="owl-carousel owl-theme owl-cate v2 js-owl-cate">
            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Audio Speakers</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Audio Speakers</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/samsung3.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Cell Phones & Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/macbook2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Audio Speakers</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/macbook2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/macbook2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="e-qa">
        <div class="cmt-title v2 text-center abs"><h1 class="oval-bd btn-gradient">Customer questions & answers</h1></div>
        <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-6">
                <div class="qa-element">
                    <div class="qa-quest">
                        <span class="qa-icon">Q</span>
                        <h3>Does the sonos one pair up with a sonos play1 as a stereo pair ?</h3>
                    </div>
                    <div class="qa-answer">
                        <span class="qa-icon">A</span>
                        <p>As long as the players are grouped in a configuration to your liking, you can ask Alexa on the Sonos One or on an echo device to start music and playback will begin across the Sonos grouping. </p>
                    </div>
                </div>

                <div class="qa-element">
                    <div class="qa-quest">
                        <span class="qa-icon">Q</span>
                        <h3>It uses a dictionary of over 200 Latin words ?</h3>
                    </div>
                    <div class="qa-answer">
                        <span class="qa-icon">A</span>
                        <p>Latin words, combined with a handful of model sentence structures,to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humou</p>
                    </div>
                </div>

                <div class="qa-element">
                    <div class="qa-quest">
                        <span class="qa-icon">Q</span>
                        <h3>Does the sonos one pair up with a sonos play1 as a stereo pair ?</h3>
                    </div>
                    <div class="qa-answer">
                        <span class="qa-icon">A</span>
                        <p>As long as the players are grouped in a configuration to your liking, you can ask Alexa on the Sonos One or on an echo device to start music and playback will begin across the Sonos grouping.  </p>
                    </div>
                </div>

                <div class="qa-element">
                    <div class="qa-quest">
                        <span class="qa-icon">Q</span>
                        <h3>It uses a dictionary of over 200 Latin words ?</h3>
                    </div>
                    <div class="qa-answer">
                        <span class="qa-icon">A</span>
                        <p>Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose </p>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-6">
                <div class="qa-element">
                    <div class="qa-quest">
                        <span class="qa-icon">Q</span>
                        <h3>Is the quality of the "sonos one" sound the same as ?</h3>
                    </div>
                    <div class="qa-answer">
                        <span class="qa-icon">A</span>
                        <p>There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look</p>
                    </div>
                </div>

                <div class="qa-element">
                    <div class="qa-quest">
                        <span class="qa-icon">Q</span>
                        <h3>Lorem Ipsum is simply dummy text of the printing and typesetting?</h3>
                    </div>
                    <div class="qa-answer">
                        <span class="qa-icon">A</span>
                        <p>If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text.</p>
                    </div>
                </div>

                <div class="qa-element">
                    <div class="qa-quest">
                        <span class="qa-icon">Q</span>
                        <h3>Is the quality of the "sonos one" sound the same as ?</h3>
                    </div>
                    <div class="qa-answer">
                        <span class="qa-icon">A</span>
                        <p>There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look</p>
                    </div>
                </div>

                <div class="qa-element">
                    <div class="qa-quest">
                        <span class="qa-icon">Q</span>
                        <h3>Lorem Ipsum is simply dummy text of the printing and typesetting?</h3>
                    </div>
                    <div class="qa-answer">
                        <span class="qa-icon">A</span>
                        <p>Search for 'lorem ipsum' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).</p>
                    </div>
                </div>
            </div>
        </div>
        <a href="#" class="btn-qa">See more answered questions<i class="ion-ios-arrow-down"></i></a>
        <form class="form_newsletter" action="#" method="post">
            <input type="text" value="" placeholder="Have a question about product ? " class="newsletter-input form-control">
            <button id="subscribe2" class="button_mini btn btn-gradient" type="submit">
                Submit<i class="ion-ios-arrow-forward"></i>
            </button>
        </form>
    </div>
    <div class="bestseller single-space">
        <div class="ecome-heading style5v3 spc5v3">
            <h1>Similar Brand</h1>
            <a href="#" class="btn-show">Shop more<i class="ion-ios-arrow-forward"></i></a>
        </div>
        <div class="owl-carousel owl-theme owl-cate v2 js-owl-cate">
            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Audio Speakers</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Audio Speakers</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/samsung3.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Cell Phones & Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/macbook2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Audio Speakers</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/macbook2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/macbook2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-item">
                <div class="pd-bd product-inner">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <div class="color-group">
                        </div>
                        <div class="element-list element-list-left">
                        </div>
                        <div class="element-list element-list-middle">
                            <p class="product-cate">Computers &amp; Accessories</p>
                            <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                            <div class="product-bottom">
                                <div class="product-price"><span>$1,215.00</span></div>
                                <a href="#" class="btn-icon btn-view">
                                    <span class="icon-bg icon-view"></span>
                                </a>
                            </div>    
                        </div>         
                        <div class="product-button-group">
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-cart"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-wishlist"></span>
                            </a>
                            <a href="#" class="btn-icon">
                                <span class="icon-bg icon-compare"></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection