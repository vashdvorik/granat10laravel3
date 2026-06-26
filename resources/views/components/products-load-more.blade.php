{{-- Партиал для AJAX-загрузки товаров --}}
@foreach($products as $product)
   @include('components.home-product-card', ['product' => $product])
@endforeach
