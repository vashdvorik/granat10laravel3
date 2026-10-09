{{-- Ссылки на городские страницы в подвале (все темы): так поисковик находит страницы городов и понимает,
     где у площадки есть товары. Показываются только города, в которых есть опубликованные товары. --}}
@if(config('regionality.urls_enabled') && \Illuminate\Support\Facades\Route::has('regional.home') && ! empty($seoCities) && $seoCities->isNotEmpty())
    <nav class="seo-cities" aria-label="Города" style="display:flex;flex-wrap:wrap;align-items:baseline;gap:6px 14px;margin:18px 0;font-size:14px;line-height:1.5">
        <span style="opacity:.75">Купить товары в городах:</span>
        @foreach($seoCities as $city)
            <a href="{{ route('regional.home', ['location' => $city->slug]) }}" title="Купить {{ $city->inPhrase() }}" style="color:inherit;text-decoration:underline">{{ $city->name }}</a>
        @endforeach
    </nav>
@endif
