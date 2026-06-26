@php
    $regions = $regionalContext->availableRegions();
    $variant = $variant ?? 'default';
@endphp

@if(config('regionality.enabled') && $regions->isNotEmpty())
    <div class="regional-selector regional-selector--{{ $variant }}">
        <button class="regional-selector__toggle dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-map-marker" aria-hidden="true"></i>
            <span>{{ $regionalContext->label() }}</span>
            <i class="fa fa-caret-down" aria-hidden="true"></i>
        </button>
        <ul class="dropdown-menu regional-selector__menu">
            <li>
                <form method="POST" action="{{ route('regionality.update') }}">
                    @csrf
                    <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                    <button type="submit" class="regional-selector__option {{ $regionalContext->isAllRegions() ? 'is-active' : '' }}">
                        Все регионы
                    </button>
                </form>
            </li>
            @foreach($regions as $region)
                <li class="regional-selector__group">{{ $region->name }}</li>
                @foreach($region->settlements as $settlement)
                    <li>
                        <form method="POST" action="{{ route('regionality.update') }}">
                            @csrf
                            <input type="hidden" name="settlement_id" value="{{ $settlement->id }}">
                            <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                            <button type="submit" class="regional-selector__option {{ $regionalContext->settlement()?->is($settlement) ? 'is-active' : '' }}">
                                {{ $settlement->name }}
                                <small>({{ $settlement->type }})</small>
                            </button>
                        </form>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </div>
@endif
