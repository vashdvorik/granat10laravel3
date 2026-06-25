@props(['issues' => []])

@if(count($issues) > 0)
    <div class="product-moderation-issues mb-3">
        <div class="product-moderation-issues__title">
            Что нужно исправить
        </div>

        <div class="product-moderation-issues__list">
            @foreach($issues as $issue)
                <div class="product-moderation-issues__item">
                    <div class="product-moderation-issues__field">
                        <a href="#{{ $issue['target'] }}">{{ $issue['label'] }}</a>

                        @if(($issue['image_index'] ?? null) !== null)
                            <span class="product-moderation-issues__image-index">
                                Фото №{{ $issue['image_index'] }}
                            </span>
                        @endif
                    </div>

                    <div class="product-moderation-issues__body">
                        @if(($issue['image_url'] ?? null) !== null)
                            <a href="#{{ $issue['target'] }}" class="product-moderation-issues__thumb">
                                <img src="{{ $issue['image_url'] }}" alt="Проблемное фото товара №{{ $issue['image_index'] }}">
                            </a>
                        @endif

                        <div class="product-moderation-issues__content">
                            <div class="product-moderation-issues__message">
                                {{ $issue['message'] }}
                            </div>

                            @if($issue['suggestion'])
                                <div class="product-moderation-issues__suggestion">
                                    <span>Рекомендация:</span> {{ $issue['suggestion'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
