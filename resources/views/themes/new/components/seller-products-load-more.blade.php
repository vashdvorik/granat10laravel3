@foreach($products as $product)
    @include('components.home-product-card', ['product' => $product])
@endforeach
