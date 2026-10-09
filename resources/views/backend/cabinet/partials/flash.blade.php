{{-- Сообщения об итоге действия (flash). В теме «new» их выводит сам layout, там этот блок переопределён пустым. --}}
@include('backend.cabinet.partials.seller-hidden-notice')
@foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $flashKey => $flashClass)
    @if(session($flashKey))
        <div class="alert alert-{{ $flashClass }} alert-dismissible fade show mb-3" role="alert">
            {{ session($flashKey) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    @endif
@endforeach
