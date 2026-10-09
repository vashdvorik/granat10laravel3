{{-- SEO-теги <head> для всех тем оформления: canonical, robots, description, Open Graph, Twitter Card, JSON-LD.
     Значения готовит SeoService / StructuredDataService через composer макета (AppServiceProvider::composeSeo). --}}
@php
    $seoPageTitle = $seoTitle ?: (trim($__env->yieldContent('title')) ?: \App\Support\PageTitle::brand());
    $seoImage = trim($__env->yieldContent('og_image')) ?: ($seoOgImage ?? null);
@endphp
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta name="robots" content="{{ $robotsMeta }}">
@if($seoDescription)
<meta name="description" content="{{ $seoDescription }}">
@endif
<meta property="og:title" content="{{ $seoPageTitle }}">
<meta property="og:type" content="{{ trim($__env->yieldContent('og_type')) ?: ($seoOgType ?? 'website') }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="{{ \App\Support\PageTitle::brand() }}">
@if($seoDescription)
<meta property="og:description" content="{{ $seoDescription }}">
@endif
@if($seoImage)
<meta property="og:image" content="{{ $seoImage }}">
@endif
<meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seoPageTitle }}">
@if($seoDescription)
<meta name="twitter:description" content="{{ $seoDescription }}">
@endif
@if($seoImage)
<meta name="twitter:image" content="{{ $seoImage }}">
@endif
@foreach(($structuredData ?? []) as $item)
<script type="application/ld+json">{!! json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach
