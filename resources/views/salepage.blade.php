@extends('main_layots')
@section('title', 'Распродажи | Маркетплейс ПМР')

@section('meta')
    {{-- SEO: Пагинация для поисковиков --}}
    @if(isset($products) && $products->hasPages())
        @if($products->previousPageUrl())
            <link rel="prev" href="{{ $products->previousPageUrl() }}" />
        @endif
        @if($products->nextPageUrl())
            <link rel="next" href="{{ $products->nextPageUrl() }}" />
        @endif
    @endif
@endsection

@section('content')

<!--content-->
<div class="container container-240">
    <div id="catalog-top"></div>
    <div class="e-product">
        <ul class="breadcrumb v4">
            <li><a href="{{ route('home') }}">Главная</a></li>
            <li class="active">Распродажи</li>
        </ul>
        <div class="pd-banner">
            <a href="#" class="image-bd effect_img2"><img src="img/shop-banner.jpg" alt="" class="img-reponsive"></a> 
        </div>
        <div class="pd-top">
            <h1 class="title">Акции сейчас</h1>
            <div class="show-element"><span>Показано {{ $products->firstItem() }}–{{ $products->lastItem() }} из {{ $products->total() }} товаров</span></div>
        </div>
        <div class="product-collection-grid product-grid spc1">
            <div class="row">
                {{-- КАРТОЧКА ТОВАРА --}} 
                @forelse($products as $product)
                    @include('components.productcategories', ['product' => $product])
                @empty
                    <p>Нет товаров в этой категории.</p>
                @endforelse 
            </div>
        </div>    
        {{--
        <div class="product-collection-grid product-grid spc1">
            <div class="row">
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/pd1.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                                <a href="#" class="circle black"></a>
                                <a href="#" class="circle red"></a>
                                <a href="#" class="circle gray"></a>
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Computers & Accessories</p>
                                <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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

                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/pd2.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group"></div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Computers & Accessories</p>
                                <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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

                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/pd3.jpg" alt="" class="img-reponsive"></a>
                            <div class="ribbon-price red"><span>- 30% </span></div>
                        </div>
                        <div class="product-info">
                            <div class="color-group"></div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">HP 22er 21.5-inch LED</a></h3>
                                <div class="product-bottom">
                                    <div class="product-price">
                                        <span class="red">$1.450.00</span>
                                        <span class="old">$1,215.00</span>
                                    </div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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

                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/pd4.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group"></div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Camera & Photo</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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

                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/headphone2.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/sound2.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/samsung3.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                                <a href="#" class="circle blue"></a>
                                <a href="#" class="circle red"></a>
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Cell Phones & Accessories</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/macbook2.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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

                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/macbook.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/headphone3.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/samsungbox.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/smartwatch.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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

                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/fujifilm.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Computers & Accessories</p>
                                <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/usb.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                                <a href="#" class="circle black"></a>
                                <a href="#" class="circle red"></a>
                                <a href="#" class="circle gray"></a>
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Computers & Accessories</p>
                                <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/samsung4.jpg" alt="" class="img-reponsive"></a>
                            <div class="ribbon-price red"><span>- 30% </span></div>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">HP 22er 21.5-inch LED</a></h3>
                                <div class="product-bottom">
                                    <div class="product-price">
                                        <span class="red">$1,215.00</span>
                                        <span class="old">$1,450.00</span>
                                    </div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/logitech.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Camera & Photo</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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

                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/tv.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/ubl.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/macbook.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/ring.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                

                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/anker.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Computers & Accessories</p>
                                <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/sound.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Computers & Accessories</p>
                                <h3 class="product-title"><a href="#">Cordless TrackMan Wheel</a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/headphone.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                                <a href="#" class="circle red"></a>
                                <a href="#" class="circle yellow"></a>
                                <a href="#" class="circle gray"></a>
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Audio Speakers</p>
                                <h3 class="product-title"><a href="#">HP 22er 21.5-inch LED</a></h3>
                                <div class="product-bottom">
                                    <div class="product-price">
                                        <span class="red">$1,215.00</span>
                                        <span class="old">$1,450.00</span>
                                    </div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
                <div class="col-xs-6 col-sm-4 col-md-4 col-lg-3 product-item">
                    <div class="pd-bd product-inner">
                        <div class="product-img">
                            <a href="#"><img src="img/product/headphone4.jpg" alt="" class="img-reponsive"></a>
                        </div>
                        <div class="product-info">
                            <div class="color-group">
                            </div>
                            <div class="element-list element-list-left">
                                <ul class="desc-list">
                                    <li>Connects directly to Bluetooth</li> 
                                    <li>Battery Indicator light</li> 
                                    <li>DPI Selection:2600/2000/1600/1200/800</li> 
                                    <li>Computers running Windows</li> 
                                </ul>
                            </div>
                            <div class="element-list element-list-middle">
                                <div class="product-rating bd-rating">
                                    <span class="star star-5"></span>
                                    <span class="star star-4"></span>
                                    <span class="star star-3"></span>
                                    <span class="star star-2"></span>
                                    <span class="star star-1"></span>
                                    <div class="number-rating">( 896 reviews )</div>
                                </div>
                                <p class="product-cate">Camera & Photo</p>
                                <h3 class="product-title"><a href="#">Harman Kardon Onyx Studio </a></h3>
                                <div class="product-bottom">
                                    <div class="product-price"><span>$1,215.00</span></div>
                                    <a href="#" class="btn-icon btn-view">
                                        <span class="icon-bg icon-view"></span>
                                    </a>
                                </div>    
                                <div class="product-bottom-group">
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
        </div> --}}
        
        {{-- ПАГИНАЦИЯ --}}
        @if($products->hasPages())
            {{-- Desktop пагинация --}}
            <div class="pagination-desktop hidden-xs hidden-sm pd-middle space-v1">
                {{ $products->links('vendor.pagination.custom') }}
            </div>
            
            {{-- Mobile: кнопка "Загрузить еще" --}}
            @if($products->currentPage() < $products->lastPage())
                <div class="load-more-wrapper hidden-md hidden-lg text-center">
                    <button class="btn btn-load-more" 
                            data-url="{{ route('catalog.loadMoreSale') }}"
                            data-current-page="{{ $products->currentPage() }}"
                            data-last-page="{{ $products->lastPage() }}">
                        <i class="ion-load-c"></i> Загрузить еще
                    </button>
                </div>
            @endif
        @endif
            <!--
            <div class="pd-sort">
                <div class="filter-sort">
                    <div class="dropdown">
                        <button class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <span class="dropdown-label">Default sorting</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="manual">Featured</a></li>   
                            <li><a href="best-selling">Best Selling</a></li>
                            <li><a href="title-ascending">Alphabetically, A-Z</a></li>
                            <li><a href="title-descending">Alphabetically, A-Z</a></li>
                            <li><a href="price-descending">Price, high to low</a></li>
                            <li><a href="price-ascending">Price, low to high</a></li>
                            <li><a href="created-ascending">Date, old to new</a></li>
                            <li><a href="created-descending">Date, new to old</a></li>
                        </ul>
                    </div>
                </div>
                <div class="filter-show">
                    <div class="dropdown">
                            <button class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                Show
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
            </div> -->

        </div>
    </div>
</div>
<div class="e-category">
    <div class="container container-240">
        <div class="row">
            <div class="col-xs-12 col-sm-4 col-md-4">
                <h1 class="cate-title">Featured Products</h1>
                <div class="cate-item">
                    <div class="product-img">
                        <a href="#"><img src="img/product/usb.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><a href="#">Epson Home Cinema 5040UB </a></h3>
                        <div class="product-price v2"><span>$780.00</span></div>
                    </div>
                </div>

                <div class="cate-item">
                    <div class="product-img">
                        <a href="#"><img src="img/product/macbook.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><a href="#">Epson Home Cinema 5040UB </a></h3>
                        <div class="product-price v2"><span>$780.00</span></div>
                    </div>
                </div>

                <div class="cate-item">
                    <div class="product-img">
                        <a href="#"><img src="img/product/flycam.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><a href="#">Epson Home Cinema 5040UB </a></h3>
                        <div class="product-price v2"><span>$780.00</span></div>
                    </div>
                </div>

            </div>

            <div class="col-xs-12 col-sm-4 col-md-4">
                <h1 class="cate-title">Top Rated Products</h1>
                <div class="cate-item">
                    <div class="product-img">
                        <a href="#"><img src="img/product/samsung.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><a href="#">Epson Home Cinema 5040UB </a></h3>
                        <div class="product-price v2"><span>$780.00</span></div>
                    </div>
                </div>

                <div class="cate-item">
                    <div class="product-img">
                        <a href="#"><img src="img/product/headphone2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><a href="#">Epson Home Cinema 5040UB </a></h3>
                        <div class="product-price v2"><span>$780.00</span></div>
                    </div>
                </div>

                <div class="cate-item">
                    <div class="product-img">
                        <a href="#"><img src="img/product/anker.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><a href="#">Epson Home Cinema 5040UB </a></h3>
                        <div class="product-price v2"><span>$780.00</span></div>
                    </div>
                </div>
                
            </div>

            <div class="col-xs-12 col-sm-4 col-md-4">
                <h1 class="cate-title">Top Selling Products</h1>
                <div class="cate-item">
                    <div class="product-img">
                        <a href="#"><img src="img/product/headphone.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><a href="#">Epson Home Cinema 5040UB </a></h3>
                        <div class="product-price v2"><span>$780.00</span></div>
                    </div>
                </div>

                <div class="cate-item">
                    <div class="product-img">
                        <a href="#"><img src="img/product/samsung2.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><a href="#">Epson Home Cinema 5040UB </a></h3>
                        <div class="product-price v2"><span>$780.00</span></div>
                    </div>
                </div>

                <div class="cate-item">
                    <div class="product-img">
                        <a href="#"><img src="img/product/sound.jpg" alt="" class="img-reponsive"></a>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><a href="#">Epson Home Cinema 5040UB </a></h3>
                        <div class="product-price v2"><span>$780.00</span></div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
{{--
<div class="feature">
    <div class="container container-240">
        <div class="feature-inside">
            <div class="feature-block text-center">
                <div class="feature-block-img"><img src="img/feature/truck.png" alt="" class="img-reponsive"></div>
                <div class="feature-info">
                    <h3>Worldwide Delivery</h3>
                    <p>With sites in 5 languages, we ship to over 200 countries & regions.</p>
                </div>
            </div>

            <div class="feature-block text-center">
                <div class="feature-block-img"><img src="img/feature/credit-card.png" alt="" class="img-reponsive"></div>
                <div class="feature-info">
                    <h3>Safe Payment</h3>
                    <p>Pay with the world’s most popular and secure payment methods.</p>
                </div>
            </div>

            <div class="feature-block text-center">
                <div class="feature-block-img"><img src="img/feature/safety.png" alt="" class="img-reponsive"></div>
                <div class="feature-info">
                    <h3>Shop with Confidence</h3>
                    <p>Our Buyer Protection covers your purchase from click to delivery.</p>
                </div>
            </div>

            <div class="feature-block text-center">
                <div class="feature-block-img"><img src="img/feature/telephone.png" alt="" class="img-reponsive"></div>
                <div class="feature-info">
                    <h3>24/7 Help Center</h3>
                    <p>Round-the-clock assistance for a smooth shopping experience.</p>
                </div>
            </div>
        </div>
    </div>
</div>--}}

@endsection<!-- / end content -->
       