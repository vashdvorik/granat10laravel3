@props(['product', 'compact' => false])

@php($state = $product->view_state)

@if($compact)
    <div class="dropdown" style="display: block; line-height: 1;">
        <button
            class="btn btn-light rounded-circle shadow-sm position-relative"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Другие действия"
            title="Другие действия"
            style="width: 38px; height: 38px; min-width: 38px; margin: 0 !important; padding: 0 !important; line-height: 1; background: rgba(255, 255, 255, 0.95);"
        >
            <i
                class="uil uil-ellipsis-v position-absolute"
                style="top: 50%; left: 50%; margin: 0; font-size: 20px; line-height: 1; transform: translate(-50%, -50%);"
            ></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-2">
            @if($state->canUnpublish)
                <form action="{{ route('account.store.products.deactivate', $product) }}"
                      method="POST"
                      class="status-change-form m-0"
                      data-action="deactivate"
                      data-product-name="{{ $product->name }}">
                    @csrf
                    @method('PATCH')
                    <button type="button" class="dropdown-item status-change-btn">
                        <i class="uil uil-pause me-2"></i> Снять с публикации
                    </button>
                </form>
            @elseif($state->canPublish)
                <form action="{{ route('account.store.products.activate', $product) }}"
                      method="POST"
                      class="status-change-form m-0"
                      data-action="activate"
                      data-product-name="{{ $product->name }}">
                    @csrf
                    @method('PATCH')
                    <button type="button" class="dropdown-item status-change-btn">
                        <i class="uil uil-check me-2"></i> Опубликовать
                    </button>
                </form>
            @elseif($state->canSubmit)
                <form action="{{ route('account.store.products.submit-moderation', $product) }}"
                      method="POST"
                      class="status-change-form m-0"
                      data-action="submit"
                      data-product-name="{{ $product->name }}">
                    @csrf
                    <button type="button" class="dropdown-item status-change-btn">
                        <i class="uil uil-shield-check me-2"></i> Отправить на проверку
                    </button>
                </form>
            @endif

            <form action="{{ route('account.store.products.destroy', $product) }}"
                  method="POST"
                  class="delete-product-form m-0"
                  data-product-name="{{ $product->name }}">
                @csrf
                @method('DELETE')
                <button type="button" class="dropdown-item text-danger delete-product-btn">
                    <i class="uil uil-trash-alt me-2"></i> Удалить
                </button>
            </form>
        </div>
    </div>
@else

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    @if($state->primaryAction === \App\Enums\ProductPrimaryAction::PushUp)
        <form action="{{ route('account.store.products.push-up', $product) }}"
              method="POST"
              class="push-up-form m-0 flex-fill"
              data-product-name="{{ $product->name }}"
              data-push-at="{{ $product->push_at?->format('d.m.Y H:i') }}">
            @csrf
            <button type="button"
                    class="btn {{ $product->push_at && $product->push_at->copy()->addHours(24)->isFuture() ? 'btn-light' : 'btn-sm btn-primary btn-transparent-primary' }} btn-default btn-squared push-up-btn w-100"
                    @disabled($product->push_at && $product->push_at->copy()->addHours(24)->isFuture())>
                <i class="uil uil-arrow-up"></i> Поднять
            </button>
        </form>
    @elseif($state->primaryAction === \App\Enums\ProductPrimaryAction::Publish)
        <form action="{{ route('account.store.products.activate', $product) }}"
              method="POST"
              class="status-change-form m-0 flex-fill"
              data-action="activate"
              data-product-name="{{ $product->name }}">
            @csrf
            @method('PATCH')
            <button type="button"
                    class="btn btn-sm btn-success btn-default btn-squared btn-transparent-success status-change-btn w-100">
                <i class="uil uil-check"></i> Опубликовать
            </button>
        </form>
    @elseif($state->primaryAction === \App\Enums\ProductPrimaryAction::Submit)
        <form action="{{ route('account.store.products.submit-moderation', $product) }}"
              method="POST"
              class="status-change-form m-0 flex-fill"
              data-action="submit"
              data-product-name="{{ $product->name }}">
            @csrf
            <button type="button"
                    class="btn btn-sm btn-primary btn-default btn-squared btn-transparent-primary status-change-btn w-100">
                <i class="uil uil-shield-check"></i> Отправить на проверку
            </button>
        </form>
    @elseif($state->primaryAction === \App\Enums\ProductPrimaryAction::Edit)
        <a href="{{ route('account.store.products.edit', $product) }}"
           class="btn btn-sm btn-primary btn-default btn-squared w-100">
            <i class="uil uil-edit"></i> Исправить товар
        </a>
    @elseif($state->primaryAction === \App\Enums\ProductPrimaryAction::ResolveUnavailable)
        <a href="{{ route('account.store.products.edit', $product) }}"
           class="btn btn-sm btn-warning btn-default btn-squared w-100">
            <i class="uil uil-exclamation-triangle"></i> {{ $state->story?->key === 'outdated' ? 'Открыть и исправить' : 'Добавить фото' }}
        </a>
    @endif
</div>
@endif
