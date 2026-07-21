@props(['issues' => []])

@if(count($issues) > 0)
    <section class="product-moderation-issues">
        <div class="product-moderation-issues__header">
            <div class="product-moderation-issues__icon" aria-hidden="true"></div>
            <div class="product-moderation-issues__heading">
                <div class="product-moderation-issues__title">Что нужно исправить</div>
            </div>
        </div>

        <div class="product-moderation-issues__list">
            @foreach($issues as $issue)
                @php
                    $hasImage = !empty($issue['image_url']);
                @endphp

                <article class="product-moderation-issues__item">
                    <div class="product-moderation-issues__top">
                        <a href="#{{ $issue['target'] }}" class="product-moderation-issues__field">
                            {{ $issue['label'] }}
                        </a>

                        @if(($issue['image_index'] ?? null) !== null)
                            <span class="product-moderation-issues__badge">
                                Фото №{{ $issue['image_index'] }}
                            </span>
                        @endif
                    </div>

                    <div class="product-moderation-issues__body {{ $hasImage ? '' : 'product-moderation-issues__body--text-only' }}">
                        @if($hasImage)
                            <a href="#{{ $issue['target'] }}" class="product-moderation-issues__thumb" aria-label="Перейти к проблемному полю">
                                <img src="{{ $issue['image_url'] }}" alt="Проблемное фото товара №{{ $issue['image_index'] }}">
                            </a>
                        @endif

                        <div class="product-moderation-issues__content">
                            <div class="product-moderation-issues__message">{{ $issue['message'] }}</div>

                            @if($issue['suggestion'])
                                <div class="product-moderation-issues__suggestion">
                                    <span class="product-moderation-issues__suggestion-label">Рекомендация:</span>
                                    <span class="product-moderation-issues__suggestion-text">{{ $issue['suggestion'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
