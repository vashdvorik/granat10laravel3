@extends('main_layots')

@section('title', 'Магазины — BIMM')

@section('content')
<nav class="bimm-breadcrumbs"><a href="{{ $regionalUrl->route('home') }}">Главная</a><span>Магазины</span></nav>
<header class="bimm-page-head"><span class="bimm-kicker">Локальные бренды</span><h1>Магазины рядом</h1><p>Откройте продавцов, их ассортимент и условия покупки в одном месте.</p></header>
@if($sellers->isNotEmpty())
    <div class="bimm-seller-grid">@foreach($sellers as $seller) @include('components.seller-card', ['seller' => $seller]) @endforeach</div>
    @if($sellers->hasPages())<div class="bimm-pagination">{{ $sellers->links('vendor.pagination.custom') }}</div>@endif
@else<div class="bimm-empty"><p>В выбранном регионе магазинов пока нет.</p></div>@endif
@endsection
