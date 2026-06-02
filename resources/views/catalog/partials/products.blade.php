{{-- Партиал для вывода списка товаров --}}
@foreach($products as $product)
    @include('components.productcategories', ['product' => $product])
@endforeach
