@extends('main_layots')

@php
    $sellerName = trim($seller->name ?? 'Магазин', '" ');
    $initial = mb_strtoupper(mb_substr($sellerName, 0, 1));
    $location = $seller->settlement?->name ?: $seller->town;
    $phoneHref = preg_replace('/[^\d+]/', '', (string) $seller->phone);
@endphp

@section('title', $sellerName.' — BIMM')

@section('content')
<nav class="bimm-breadcrumbs"><a href="{{ $regionalUrl->route('home') }}">Главная</a><a href="{{ $regionalUrl->route('shops') }}">Магазины</a><span>{{ $sellerName }}</span></nav>
<section class="bimm-seller-hero">
    <span class="bimm-seller-hero__logo">@if($seller->photo)<img src="{{ asset('storage/'.ltrim($seller->photo, '/')) }}" alt="{{ $sellerName }}">@else{{ $initial }}@endif</span>
    <div><span class="bimm-kicker">{{ $location ?: 'Локальный магазин' }}</span><h1>{{ $sellerName }}</h1><p>{{ $seller->description ?: 'Ассортимент и предложения магазина на BIMM.' }}</p></div>
    <div class="bimm-seller-hero__contact">@if($seller->phone)<a href="tel:{{ $phoneHref }}"><i class="fa-solid fa-phone"></i> {{ $seller->phone }}</a>@endif @if($seller->address)<span>{{ $seller->address }}</span>@endif @if($seller->link_map)<a href="{{ $seller->link_map }}" target="_blank" rel="noopener">Открыть на карте</a>@endif</div>
</section>
<section class="bimm-section"><div class="bimm-section__head"><div><span class="bimm-kicker">Ассортимент</span><h2>Товары магазина</h2></div></div>@if($products->isNotEmpty())<div class="bimm-product-grid">@foreach($products as $product) @include('components.home-product-card', ['product' => $product]) @endforeach</div>@if($products->hasPages())<div class="bimm-pagination">{{ $products->links('vendor.pagination.custom') }}</div>@endif @else<div class="bimm-empty"><p>Опубликованных товаров пока нет.</p></div>@endif</section>
@endsection
