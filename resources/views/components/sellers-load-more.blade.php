@foreach($sellers as $seller)
    @include('components.seller-card', ['seller' => $seller])
@endforeach
