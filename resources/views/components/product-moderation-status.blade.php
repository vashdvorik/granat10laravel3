@props(['product', 'hasIssues' => false])

{{-- Состояние товара человеческим языком: что это значит, видно ли покупателям, что делать дальше.
     Тексты берутся из ProductStatusStoryFactory — единого источника для карточки, страницы и уведомлений. --}}
@php($story = $product->view_state->story)

<div class="product-status-box product-status-box--{{ $story->tone }}" id="product-status-box" role="status">
    <div class="product-status-box__head">
        <span class="product-status-box__icon" aria-hidden="true"><i class="uil uil-{{ $story->icon }}"></i></span>
        <div class="product-status-box__titles">
            <strong class="product-status-box__title">{{ $story->headline }}</strong>
            <span class="product-status-box__text">{{ $story->explanation }}</span>
        </div>
    </div>

    {{-- Если замечания разобраны по полям, их показывает список «Что нужно исправить» ниже --}}
    @if($story->remark && ! $hasIssues)
        <div class="product-status-box__remark">
            <b>{{ $story->remarkTitle }}:</b> {{ $story->remark }}
        </div>
    @endif

    @if($story->nextStep)
        <div class="product-status-box__next">
            <i class="uil uil-arrow-right" aria-hidden="true"></i> {{ $story->nextStep }}
        </div>
    @endif
</div>
