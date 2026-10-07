@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
<div class="contents events-page">
    <div class="container-fluid">
        <div class="events-hero">
            <div>
                <span class="events-hero__eyebrow">Журнал кабинета</span>
                <h4>Важные события магазина</h4>
                <p>Здесь собраны изменения товаров, магазина и заказов. Журнал помогает понять, почему товар пропал из публикации или заказ сменил статус.</p>
            </div>
            <a href="{{ route('account.index') }}" class="btn btn-sm btn-outline-primary">К магазину</a>
        </div>

        <div class="events-summary">
            <div>
                <span>Магазин</span>
                <strong>{{ $seller->name }}</strong>
            </div>
            <div>
                <span>Последние события</span>
                <strong>{{ count($events) }}</strong>
            </div>
        </div>

        @if($events !== [])
            <div class="event-timeline">
                @foreach($events as $event)
                    <article class="event-timeline__item event-timeline__item--{{ $event['tone'] }}">
                        <span class="event-timeline__marker" aria-hidden="true"></span>
                        <div class="event-timeline__content">
                            <div class="event-timeline__head">
                                <time datetime="{{ $event['date']->toIso8601String() }}">
                                    {{ $event['date']->format('d.m.Y H:i') }}
                                </time>
                                <span>{{ $event['tone'] === 'success' ? 'Готово' : ($event['tone'] === 'warning' ? 'Внимание' : ($event['tone'] === 'danger' ? 'Важно' : 'Инфо')) }}</span>
                            </div>
                            <h5>{{ $event['title'] }}</h5>
                            @if($event['description'])
                                <p>{{ $event['description'] }}</p>
                            @endif
                            @if($event['url'] && $event['action'])
                                <a href="{{ $event['url'] }}" class="event-timeline__action">{{ $event['action'] }}</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="events-empty">
                <span class="uil uil-history"></span>
                <h5>Событий пока нет</h5>
                <p>Когда вы измените товар, настройки магазина или статус заказа, важные действия появятся здесь.</p>
                <a href="{{ route('account.index') }}" class="btn btn-primary">Открыть магазин</a>
            </div>
        @endif
    </div>
</div>
@endsection
