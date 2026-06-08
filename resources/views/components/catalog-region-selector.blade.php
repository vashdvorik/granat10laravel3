@php
    $regions = $regionalContext->availableRegions();
@endphp

@if(config('regionality.enabled') && $regions->isNotEmpty())
    <div class="filter-sort">
        <div class="dropdown">
            <button class="dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                <span class="dropdown-label">{{ $regionalContext->label() }}</span>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <form id="catalog-region-all" method="POST" action="{{ route('regionality.update') }}">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                    </form>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('catalog-region-all').submit();">
                        {{ $regionalContext->isAllRegions() ? '✓ ' : '' }}Все регионы
                    </a>
                </li>
                @foreach($regions as $region)
                    <li class="catalog-region-heading"><strong>{{ $region->name }}</strong></li>
                    @foreach($region->settlements as $settlement)
                        <li>
                            <form id="catalog-region-{{ $settlement->id }}" method="POST" action="{{ route('regionality.update') }}">
                                @csrf
                                <input type="hidden" name="settlement_id" value="{{ $settlement->id }}">
                                <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                            </form>
                            <a href="#" onclick="event.preventDefault(); document.getElementById('catalog-region-{{ $settlement->id }}').submit();">
                                {{ $regionalContext->settlement()?->is($settlement) ? '✓ ' : '' }}{{ $settlement->name }}
                            </a>
                        </li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    </div>
@endif
