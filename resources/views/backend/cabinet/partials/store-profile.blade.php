{{-- Профиль магазина: основная информация и контакты. Общий блок для обеих тем кабинета. --}}
<div class="card mb-25 store-overview-card">
    <div class="card-header">
        <h6 class="fw-500 mb-0">Основная информация</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 border rounded bg-light-subtle store-info-tile">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="uil uil-phone text-success fs-24"></i>
                        </div>
                        <div class="flex-grow-1">
                            <small class="text-muted d-block mb-1">Телефон магазина</small>
                            <strong class="d-block">{{ $seller->phone ?? 'Не указан' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 border rounded bg-light-subtle store-info-tile">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="uil uil-map-marker text-danger fs-24"></i>
                        </div>
                        <div class="flex-grow-1">
                            <small class="text-muted d-block mb-1">Город и район</small>
                            <strong class="d-block">{{ collect([$seller->town, $seller->sellerplace])->filter()->implode(', ') ?: 'Не указаны' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="p-3 border rounded bg-light-subtle store-info-tile">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="uil uil-map-pin-alt text-warning fs-24"></i>
                        </div>
                        <div class="flex-grow-1">
                            <small class="text-muted d-block mb-1">Адрес</small>
                            <strong class="d-block">{{ $seller->address ?? 'Не указан' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            @if($seller->description)
                <div class="col-12">
                    <div class="p-3 border rounded bg-light-subtle store-info-tile">
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <i class="uil uil-file-alt text-secondary fs-24"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block mb-1">Описание</small>
                                <p class="mb-0">{{ $seller->description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="card-footer bg-transparent border-top">
        <div class="d-flex justify-content-end">
            <a href="{{ route('account.store.edit', $seller->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="uil uil-edit"></i> Редактировать
            </a>
        </div>
    </div>
</div>

{{-- Контакты и соцсети: только заполненные, пустые — одной строкой --}}
@php
    $contactItems = [
        ['title' => 'Веб-сайт', 'icon' => 'uil uil-globe', 'value' => $seller->link, 'url' => $seller->link],
        ['title' => 'Google Maps', 'icon' => 'uil uil-map', 'value' => $seller->link_map ? 'Открыть на карте' : null, 'url' => $seller->link_map],
        ['title' => 'Telegram', 'icon' => 'uil uil-telegram', 'value' => $seller->telegram, 'url' => null],
        ['title' => 'Instagram', 'icon' => 'uil uil-instagram', 'value' => $seller->instagram, 'url' => null],
        ['title' => 'Viber', 'icon' => 'uil uil-phone-volume', 'value' => $seller->viber, 'url' => null],
    ];
    $filledContacts = array_values(array_filter($contactItems, fn ($item) => filled($item['value'])));
    $missingContacts = array_values(array_map(
        fn ($item) => $item['title'],
        array_filter($contactItems, fn ($item) => ! filled($item['value'])),
    ));
@endphp
<div class="card mb-25 store-overview-card store-contact-card">
    <div class="card-header">
        <h6 class="fw-500 mb-0">Контакты и соцсети</h6>
    </div>
    <div class="card-body">
        @if($filledContacts !== [])
            <div class="d-flex flex-column gap-2 {{ $missingContacts !== [] ? 'mb-3' : '' }}">
                @foreach($filledContacts as $contact)
                    <div class="d-flex align-items-center gap-3 p-3 border rounded bg-light-subtle store-info-tile">
                        <i class="{{ $contact['icon'] }} text-primary fs-24"></i>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <small class="text-muted d-block mb-1">{{ $contact['title'] }}</small>
                            @if($contact['url'])
                                <a href="{{ $contact['url'] }}" target="_blank" rel="noopener" class="d-block text-truncate fw-bold">
                                    {{ \Illuminate\Support\Str::limit((string) $contact['value'], 48) }}
                                </a>
                            @else
                                <strong class="d-block text-break">{{ $contact['value'] }}</strong>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($missingContacts !== [])
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <span class="text-muted">
                    {{ $filledContacts === [] ? 'Контакты пока не добавлены' : 'Не указаны' }}: {{ implode(', ', $missingContacts) }}
                </span>
                <a href="{{ route('account.store.edit', $seller->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="uil uil-plus"></i> Добавить
                </a>
            </div>
        @endif
    </div>
</div>
