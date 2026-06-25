@props(['product', 'hasIssues' => false])

@php($state = $product->view_state)

<div
    class="alert alert-light border mb-3"
    style="display: block; min-width: 0; overflow-wrap: anywhere;"
>
    <div style="display: block; width: 100%;">
        <strong>Модерация:</strong> {{ $state->moderationLabel }}
    </div>
    <div style="display: block; width: 100%; margin-top: 4px;">
        <strong>Публикация:</strong> {{ $state->publicationLabel }}
    </div>
    @if(! $hasIssues || ! $product->moderation_summary || ! str_contains($state->description, $product->moderation_summary))
        <div class="mt-2" style="display: block; width: 100%;">
            {{ $state->description }}
        </div>
    @endif
    @if(! $hasIssues && $product->moderation_summary && ! str_contains($state->description, $product->moderation_summary))
        <div class="mt-2 text-danger">{{ $product->moderation_summary }}</div>
    @endif
</div>
