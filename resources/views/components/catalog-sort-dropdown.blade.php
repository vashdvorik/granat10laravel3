@php
    $currentSort = request('sort', 'new');
    $sortOptions = [
        'new' => 'Сначала новые',
        'price_asc' => 'Сначала дешевые',
        'sale' => 'Сначала акции',
    ];
    $currentLabel = $sortOptions[$currentSort] ?? $sortOptions['new'];
    $sortUrl = function (string $sort): string {
        $query = request()->except('page');

        if ($sort === 'new') {
            unset($query['sort']);
        } else {
            $query['sort'] = $sort;
        }

        $queryString = http_build_query($query);

        return request()->url().($queryString ? '?'.$queryString : '');
    };
@endphp

<div class="filter-show">
    <div class="dropdown">
        <button class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
            Сортировать по
            <span class="dropdown-label">{{ $currentLabel }}</span>
        </button>
        <ul class="dropdown-menu">
            @foreach($sortOptions as $sortValue => $sortLabel)
                <li>
                    <a href="{{ $sortUrl($sortValue) }}" @class(['active' => $currentSort === $sortValue])>
                        {{ $sortLabel }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
