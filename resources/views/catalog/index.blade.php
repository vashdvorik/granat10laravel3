@extends('main_layots')
@section('title', 'Каталог товара | Маркетплейс ПМР')

@section('content')
<div class="container container-240 shop-collection">
    <ul class="breadcrumb">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li class="active">Каталог</li>
    </ul>
<!--<div class="filter-collection-left hidden-lg hidden-md">
      <a class="btn">Filter</a>
    </div>
    <div class="row shop-colect"> -->
    <!--СИТИБАР СЛЕВА  -->
        <div class="col-md-3 col-sm-3 col-xs-12 col-left collection-sidebar" id="filter-sidebar">
            <div class="close-sidebar-collection hidden-lg hidden-md">
                <span>filter</span><i class="icon_close ion-close"></i>
            </div> 

            <!-- МЕНЮ-->
            <div class="filter filter-cate ">
                <ul class="wiget-content v2">
                    @foreach ($categories as $subcategory)
                     <li class="{{ $subcategory->children->isNotEmpty() ? 'active' : '' }}">
                        <a href="{{ route('catalog.show', $subcategory->alias) }}">
                        {{ $subcategory->name }}
                        <span class="number">
                            ({{ $categoryProductsCounts[$subcategory->id] ?? 0 }})
                        </span>
                        </a>
                     </li>
                    @endforeach 
                </ul>
            </div>
            <!-- / МЕНЮ-->

            <!--ФИЛЬТРЫ  -->
            <form action="{{ route('catalog.index') }}" method="GET">
            <div class="filter filter-group">
                <h1 class="widget-blog-title">Товарный фильтр</h1>
    {{--        <div class="filter-price filter-inside">
                    <h3>Цена</h3>
                    <div class="filter-content">
                        <div class="price-range-holder">
                            <input type="text" class="price-slider" value=""
                            data-min="{{ $minPrice }}" data-max="{{ $maxPrice }}">
                            <input type="hidden" id="min_price" name="min_price" value="{{ request('min_price', $minPrice) }}">
                            <input type="hidden" id="max_price" name="max_price" value="{{ request('max_price', $maxPrice) }}">
                        </div>
                        <span class="min-max">
                            Цена: {{ request('min_price', $minPrice) }} — {{ request('max_price', $maxPrice) }}
                        </span>
                        <button type="submit" class="btn-filter e-gradient">Фильтровать</button>
                    </div>
                </div> --}}
                <div class="filter-brand filter-inside">
    <h3>Продавцы</h3>
    <ul class="e-filter brand-filter">
        <li class="{{ request('seller') == null ? 'active-filter' : '' }}">
            <a href="{{ route('catalog.index') }}">Все продавцы <span class="number"></span></a>
        </li>
        @foreach($sellers as $seller)
            <li class="{{ request('seller') == $seller->alias ? 'active-filter' : '' }}">
                <a href="{{ route('catalog.index', ['seller' => $seller->alias]) }}">
                    {{ $seller->name }} <span class="number">({{ $seller->product_count }})</span>
                </a>
            </li>
        @endforeach
                {{--    @foreach($sellers as $seller)
                            <li>
                                <a href="#">{{ $seller->name }} <span class="number">({{ $seller->product_count }})</span></a>
                            </li>
                        @endforeach   --}} 
            <!--        <li><a href="#">Piazza Italia <span class="number">(1)</span></a></li>
                        <li class="active-filter"><a href="#">Nord <span class="number">(50)</span></a></li>
                        <li><a href="#">Бум <span class="number">(70)</span></a></li>
                        <li><a href="#">Стиляги <span class="number">(34)</span></a></li>
                        <li><a href="#">LUXURY <span class="number">(20)</span></a></li>
                        <li><a href="#">LO.Ok <span class="number">(15)</span></a></li> -->
                    </ul>
                    <a href="#" class="btn-showmore">Больше +</a>
                </div>
        {{--    <div class="filter-color filter-brand filter-inside">
                    <h3>г. Тирасполь</h3>
                    <ul class="e-filter brand-filter">
                        <li><a href="#">Не выбрано <span class="number">(10)</span></a></li>
                        <li><a href="#">Центр <span class="number">(8)</span></a></li>
                        <li class="active-filter"><a href="#">Балка <span class="number">(15)</span></a></li>
                        <li><a href="#">Промзона <span class="number">(21)</span></a></li>
                        <li><a href="#">Бородинка  <span class="number">(104)</span></a></li>
                        <li><a href="#">Красные казармы  <span class="number">(104)</span></a></li>
                        <li><a href="#">Западный  <span class="number">(104)</span></a></li>
                    </ul>
                    <a href="#" class="btn-showmore">Больше +</a>
                </div> --}}  
            </div>
            </form>
            <!--/ФИЛЬТРЫ  -->

            <!--ТОП ТОВАРЫ  -->
            <div class="filter filter-product e-category">
                <h1 class="widget-blog-title">Топ товары</h1>
                <div class="owl-carousel owl-theme js-owl-post">
                    <div class="item">
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone1.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema  </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone2.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema  </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone3.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone4.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone5.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="item">
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone1.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema  </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone2.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema  </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone3.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone4.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone5.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                    </div>

                                                <div class="item">
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone1.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema  </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone2.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema  </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone3.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone4.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                        <div class="cate-item">
                            <div class="product-img">
                                <a href="#"><img src="img/product/phone5.jpg" alt="" class="img-reponsive"></a>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><a href="#">Epson Home Cinema </a></h3>
                                <div class="product-price v2"><span>$780.00</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ТОП ТОВАРЫ  -->

            <!--РЕКЛАМНЫЙ БАННЕР 320*384-->
    {{--       <div class="banner">
                <a class="image-bd hover-images" href=""><img src="{{ asset('img/o-banner3.jpg') }}" alt="" class="img-reponsive"></a>
            </div> --}} 
        </div>
    <!--// СИТИБАР СЛЕВА  -->

    
        <div class="col-md-9 col-sm-12 col-xs-12 collection-list">
            <div class="e-product">
                <div class="pd-banner">
                   <a href="#" class="image-bd effect_img2"><img src="{{ asset('img/shop-banner.jpg') }}" alt="" class="img-reponsive"></a> 
                </div>
                <div class="pd-top">
                    <h1 class="title">Каталог</h1>
                    <div class="show-element"><span>Showing 1–15 of 20 results</span></div>
                </div>
                <div class="pd-middle">
                    <div class="view-mode view-group">
                        <a class="grid-icon col active"><img src="img/grid.png" alt=""></a>
                        <a class="grid-icon col2"><img src="img/grid2.png" alt=""></a>
                        <a class="list-icon list"><img src="img/list.png" alt=""></a>
                    </div>
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
                    </div>
                </div>
                <div class="product-collection-grid product-grid">
                    <div class="row">
                        {{-- КАРТОЧКА ТОВАРА --}} 
                        @forelse($products as $product)
                            @include('components.productcategories', ['product' => $product])
                        @empty
                            <p>Нет товаров в этой категории.</p>
                        @endforelse
{{--    <!--                       
                        <div class="col-xs-6 col-sm-6 col-md-4 col-lg-4 product-item">
                            <div class="pd-bd product-inner">
                                <div class="product-img">
                                    <a href="#"><img src="img/test/1.jpg" alt="" class="img-reponsive"></a>
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
                                        <p class="product-cate">Магазин:Nord (800 метров)</p>
                                        <h3 class="product-title"><a href="#">Контрастное Кружевное Платье...</a></h3>
                                        <div class="product-bottom">
                                            <div class="product-price"><span>280 руб.</span></div>
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

                        <div class="col-xs-6 col-sm-6 col-md-4 col-lg-4 product-item">
                            <div class="pd-bd product-inner">
                                <div class="product-img">
                                    <a href="#"><img src="img/test/2.jpg" alt="" class="img-reponsive"></a>
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
                                        <p class="product-cate">Магазин: Nord (800 метров)</p>
                                        <h3 class="product-title"><a href="#">Платье с V-образным вырезом ...</a></h3>
                                        <div class="product-bottom">
                                            <div class="product-price">
                                                <span class="red">450 руб.</span>
                                                <span class="old">650 руб.</span>
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

                        <div class="col-xs-6 col-sm-6 col-md-4 col-lg-4 product-item">
                            <div class="pd-bd product-inner">
                                <div class="product-img">
                                    <a href="#"><img src="img/test/3.jpg" alt="" class="img-reponsive"></a>
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
                                        <p class="product-cate">Магазин: Nord (800 метров)</p>
                                        <h3 class="product-title"><a href="#">Платье с квадратным вырезом ... </a></h3>
                                        <div class="product-bottom">
                                            <div class="product-price"><span>840 руб.</span></div>
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
--> --}}
 
                        
                    </div>
                </div>
                <div class="pd-middle space-v1">
                    <ul class="pagination">
                        <li class="active"><a href="#">1</a></li>
                        <li><a href="#">2</a></li>
                        <li><a href="#"><i class="ion-ios-arrow-forward"></i></a></li>
                    </ul>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection