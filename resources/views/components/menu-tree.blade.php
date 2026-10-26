{{--
@foreach($items as $item)
<li class="level{{ $level ?? 1 }} {{ $loop->first && ($level ?? 1) == 1 ? 'active dropdown' : '' }}">
    <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $item->alias ?? $item->id]) }}">{{ $item->name }}</a>
    @if(isset($item->children) && $item->children->count())
        <span class="icon-sub-menu"></span>
        <div class="menu-level{{ $level ?? 1 }} js-open-menu">
            <ul class="level{{ ($level ?? 1) }}">
                @foreach($item->children as $child)
                    @include('components.menu-tree', ['items' => collect([$child]), 'level' => ($level ?? 1) + 1])
                @endforeach
            </ul>
            <div class="clearfix"></div>
        </div>
    @endif
</li>
@endforeach
--}} 

@foreach($items as $item)
    @if(($level ?? 1) == 1)
        <li class="level1 {{ $loop->first ? 'active dropdown' : '' }}">
            <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $item->alias ?? $item->id]) }}">{{ $item->name }}</a>
            @if(! ($shallow ?? false) && isset($item->children) && $item->children->count())
                <span class="icon-sub-menu"></span>
                <div class="menu-level1 js-open-menu">
                    <ul class="level1">
                        @foreach($item->children as $childLevel2)
                            @include('components.menu-tree', ['items' => collect([$childLevel2]), 'level' => 2])
                        @endforeach
                    </ul>
                    <div class="clearfix"></div>
                </div>
            @endif
        </li>
    @elseif($level == 2)
        <li class="level2">
            <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $item->alias ?? $item->id]) }}">{{ $item->name }}</a>
            @if(isset($item->children) && $item->children->count())
                <ul class="menu-level-2">
                    @foreach($item->children as $childLevel3)
                        <li class="level3">
                            <a href="{{ $regionalUrl->route('catalog.show', ['alias' => $childLevel3->alias ?? $childLevel3->id]) }}">{{ $childLevel3->name }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endif
@endforeach
