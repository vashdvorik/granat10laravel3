@extends('main_layots')

@section('title', $q ? 'Поиск: '.$q.' — BIMM' : 'Поиск — BIMM')

@section('content')
<header class="bimm-page-head"><span class="bimm-kicker">Поиск</span><h1>{{ $q ? 'Результаты для «'.$q.'»' : 'Найдите свой товар' }}</h1><p>{{ $products->total() }} предложений в каталоге.</p></header>
<form class="bimm-search" action="{{ $regionalUrl->route('search') }}" method="GET" style="max-width:620px;margin-bottom:28px"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="{{ $q }}" placeholder="Название товара"><button class="bimm-button bimm-button--dark" type="submit">Найти</button></form>
@if($products->isNotEmpty())<div class="bimm-product-grid">@foreach($products as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>@if($products->hasPages())<div class="bimm-pagination">{{ $products->links('vendor.pagination.custom') }}</div>@endif
@else<div class="bimm-empty"><p>Ничего не найдено. Попробуйте изменить запрос.</p></div>@endif
@endsection
