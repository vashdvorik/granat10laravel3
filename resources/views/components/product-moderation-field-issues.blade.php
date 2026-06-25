@props(['issues' => [], 'field'])

@php($fieldIssues = collect($issues)->where('field', $field))

@if($fieldIssues->isNotEmpty())
    <div class="alert alert-warning py-2 px-3 mt-2 mb-0" role="alert">
        @foreach($fieldIssues as $issue)
            <div @class(['mb-2' => ! $loop->last])>
                <strong>Замечание:</strong> {{ $issue['message'] }}
                @if($issue['suggestion'])
                    <div class="mt-1">Рекомендация: {{ $issue['suggestion'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif
