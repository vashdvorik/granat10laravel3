{{-- Партиал для AJAX-загрузки товаров --}}
@foreach($products as $product)
   @include('components.productcategories', ['product' => $product])
@endforeach
