@props(['product', 'issues' => [], 'fieldLinkBase' => ''])

{{-- Единый блок состояния товара: что это значит, что делать дальше и — если проверка нашла ошибки — в чём они.
     Тексты состояния берутся из ProductStatusStoryFactory, замечания — из ProductModerationIssuePresenter.
     fieldLinkBase: адрес формы редактирования для ссылок на поля (на самой форме не нужен, там якоря работают на месте). --}}
@php
    $story = $product->view_state->story;
    $issues = array_values((array) $issues);
@endphp

<div class="product-status-box product-status-box--{{ $story->tone }}" id="product-status-box" role="status">
    <div class="product-status-box__head">
        <span class="product-status-box__icon" aria-hidden="true"><i class="uil uil-{{ $story->icon }}"></i></span>
        <div class="product-status-box__titles">
            <strong class="product-status-box__title">{{ $story->headline }}</strong>
            <span class="product-status-box__text">{{ $story->explanation }}@if($story->nextStep) {{ $story->nextStep }}@endif</span>
        </div>
    </div>

    @if(count($issues) > 0)
        <ul class="product-status-box__issues">
            @foreach($issues as $issue)
                @php
                    $thumbs = collect($issue['images'] ?? [])->filter(fn ($image) => ! empty($image['url']))->take(3);
                    $photoNumbers = collect($issue['images'] ?? [])->pluck('index')->filter()->implode(', ');
                @endphp
                <li class="product-status-box__issue">
                    @if($thumbs->isNotEmpty())
                        <span class="product-status-box__thumbs">
                            @foreach($thumbs as $image)
                                <a href="{{ $fieldLinkBase }}#{{ $issue['target'] }}">
                                    <img src="{{ $image['url'] }}" alt="Проблемное фото товара №{{ $image['index'] }}" loading="lazy">
                                </a>
                            @endforeach
                        </span>
                    @endif

                    <div class="product-status-box__issue-body">
                        <div class="product-status-box__issue-field">
                            @foreach($issue['fields'] ?? [['label' => $issue['label'], 'target' => $issue['target']]] as $field)<a href="{{ $fieldLinkBase }}#{{ $field['target'] }}">{{ $field['label'] }}</a>@if(! $loop->last), @endif@endforeach
                            @if($photoNumbers !== '')
                                <span class="product-status-box__issue-photo">· фото №{{ $photoNumbers }}</span>
                            @endif
                        </div>
                        <div class="product-status-box__issue-message">{{ $issue['message'] }}</div>
                        @if(! empty($issue['suggestion']))
                            <div class="product-status-box__issue-tip"><b>Что сделать:</b> {{ $issue['suggestion'] }}</div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @elseif($story->remark)
        {{-- Замечания не разобраны по полям — показываем общий текст проверяющего --}}
        <div class="product-status-box__remark">
            @if($story->remarkTitle)<b>{{ $story->remarkTitle }}:</b> @endif{{ $story->remark }}
        </div>
    @elseif($story->key === 'changes_required')
        {{-- Товар вернули на доработку, но текста замечаний нет: не оставляем продавца гадать --}}
        <div class="product-status-box__remark">
            Подробных замечаний нет. Проверьте {{ \App\Services\ProductStatusStoryFactory::RECHECK_FIELDS }} или напишите администрации.
        </div>
    @endif
</div>
