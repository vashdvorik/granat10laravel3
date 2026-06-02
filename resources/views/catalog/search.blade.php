@extends('main_layots')
@section('title', 'Поиск: ' . $searchQuery . ' | Маркетплейс ПМР')

@section('content')
<div class="container container-240 shop-collection">
    <ul class="breadcrumb">
        <li><a href="{{ route('home') }}">Главная</a></li>
        <li><a href="{{ route('catalog.index') }}">Каталог</a></li>
        <li class="active">Поиск: {{ $searchQuery }}</li>
    </ul>

    <div class="row shop-colect">
        <!-- Sidebar -->
        <div class="col-md-3 col-sm-3 col-xs-12 col-left collection-sidebar">
            <div class="filter filter-cate">
                <h3>Категории</h3>
                <ul class="wiget-content v2">
                    <li><a href="{{ route('search', ['q' => $searchQuery]) }}">Все категории</a></li>
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('search', ['q' => $searchQuery, 'category_id' => $category->id]) }}">
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Products -->
        <div class="col-md-9 col-sm-9 col-xs-12 col-righ collection-content">
            <h1>Результаты поиска: "{{ $searchQuery }}"</h1>
            <p>Найдено товаров: {{ $products->total() }}</p>

            @if($products->isEmpty())
                <div class="alert alert-info">
                    <p>По вашему запросу ничего не найдено. Попробуйте изменить условия поиска.</p>
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
        var searchQuery = '{{ $searchQuery }}';
        
        $.ajax({
            url: '{{ route("search.loadMore") }}',
            type: 'GET',
            data: {
                q: searchQuery,
                page: page,
                category_id: '{{ request("category_id") }}'
            },
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
