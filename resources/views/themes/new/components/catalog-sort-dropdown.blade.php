@php
    $sortOptions = ['new' => 'Сначала новые', 'price_asc' => 'Сначала дешевле', 'sale' => 'Сначала акции'];
    $currentSort = request('sort', 'new');
@endphp
<form method="GET" action="{{ request()->url() }}" style="margin:0"><select name="sort" aria-label="Сортировка" onchange="this.form.submit()">@foreach($sortOptions as $value => $label)<option value="{{ $value }}" @selected($currentSort === $value)>{{ $label }}</option>@endforeach</select>@foreach(request()->except(['sort','page']) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach</form>
