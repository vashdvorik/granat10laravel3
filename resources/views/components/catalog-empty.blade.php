{{-- Каталог или категория без товаров: понятная карточка вместо схлопнувшегося меню слева и одной строки «Нет товаров».
     $category (необязательно): текущая категория; $backUrl и $backText (необязательно): кнопка возврата. --}}
@php
    $emptyCategory = $category ?? null;
    $emptySettlement = config('regionality.enabled') ? $regionalContext->settlement() : null;
    $emptyFiltered = request()->filled('seller');
    $emptyScope = $emptyCategory ? ' в категории «'.$emptyCategory->name.'»' : '';

    if ($emptyFiltered) {
        $emptyTitle = 'По выбранным условиям товаров нет';
        $emptyHint = 'Сбросьте фильтр или загляните в другой раздел каталога.';
    } elseif ($emptySettlement) {
        $emptyTitle = \Illuminate\Support\Str::ucfirst($emptySettlement->inPhrase()).$emptyScope.' пока нет товаров';
        $emptyHint = 'Попробуйте посмотреть все регионы или выбрать другой раздел каталога.';
    } else {
        $emptyTitle = $emptyCategory ? 'В категории «'.$emptyCategory->name.'» пока нет товаров' : 'Товаров пока нет';
        $emptyHint = 'Загляните позже или выберите другой раздел каталога.';
    }
@endphp

<section class="catalog-empty" role="status">
    <span class="catalog-empty__icon" aria-hidden="true"><i class="fa fa-box-open"></i></span>
    <h2>{{ $emptyTitle }}</h2>
    <p>{{ $emptyHint }}</p>
    <div class="catalog-empty__actions">
        @if($emptyFiltered)
            <a href="{{ url()->current() }}" class="home-btn home-btn--primary">Сбросить фильтр</a>
        @elseif($emptySettlement)
            <form method="POST" action="{{ route('regionality.update') }}">
                @csrf
                <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                <button type="submit" class="home-btn home-btn--primary">Показать все регионы</button>
            </form>
        @endif
        @isset($backUrl)
            <a href="{{ $backUrl }}" class="home-btn {{ $emptyFiltered || $emptySettlement ? 'home-btn--light' : 'home-btn--primary' }}">{{ $backText ?? 'Назад' }}</a>
        @endisset
    </div>
</section>
