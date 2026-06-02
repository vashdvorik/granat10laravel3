@extends('main_layots')
@section('title', 'Распродажа | Маркетплейс ПМР')

@section('content')
<div class="container container-240 shop-collection">
    <ul class="breadcrumb">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li><a href="{{ route('catalog.index') }}">Каталог</a></li>
        <li class="active">Распродажа</li>
    </ul>

    <div class="row shop-colect">
        <!-- Sidebar -->
        <div class="col-md-3 col-sm-3 col-xs-12 col-left collection-sidebar">
            <div class="filter filter-cate">
                <h3>Категории</h3>
                <ul class="wiget-content v2">
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('catalog.show', $category->alias) }}">
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Products -->
        <div class="col-md-9 col-sm-9 col-xs-12 col-righ collection-content">
            <h1>Распродажа</h1>
            <p>Товары со скидкой: {{ $products->total() }}</p>

            @if($products->isEmpty())
                <div class="alert alert-info">
                    <p>В данный момент нет товаров со скидкой.</p>
                </div>
            @else
                <div class="row" id="products-container">
                    @include('catalog.partials.products', ['products' => $products])
                </div>

                @if($products->hasMorePages())
                    <div class="text-center">
                        <button class="btn btn-primary" id="load-more-btn" data-page="2">
                            Загрузить еще
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#load-more-btn').on('click', function() {
        var btn = $(this);
        var page = btn.data('page');
        
        $.ajax({
            url: '{{ route("catalog.loadMoreSale") }}',
            type: 'GET',
            data: { page: page },
            beforeSend: function() {
                btn.prop('disabled', true).text('Загрузка...');
            },
            success: function(response) {
                $('#products-container').append(response.html);
                btn.data('page', page + 1);
                
                if (!response.hasMore) {
                    btn.hide();
                } else {
                    btn.prop('disabled', false).text('Загрузить еще');
                }
            },
            error: function() {
                btn.prop('disabled', false).text('Ошибка. Попробуйте еще раз');
            }
        });
    });
});
</script>
@endpush
@endsection
