@extends('voyager::master')

@section('page_title', 'Журнал действий')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-list"></i> Журнал действий
    </h1>
@stop

@section('content')
<div class="page-content container-fluid">

    {{-- Фильтры --}}
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.activity-log') }}" class="form-inline">
                        {{-- Фильтр по группе --}}
                        <div class="form-group" style="margin-right: 10px;">
                            <label class="sr-only">Группа</label>
                            <select name="log_name" class="form-control" onchange="this.form.submit()">
                                <option value="">Все группы</option>
                                @foreach ($logNames as $name)
                                    <option value="{{ $name }}" {{ request('log_name') === $name ? 'selected' : '' }}>
                                        {{ ucfirst($name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Поиск --}}
                        <div class="form-group" style="margin-right: 10px;">
                            <label class="sr-only">Поиск</label>
                            <input type="text" name="search" class="form-control"
                                placeholder="Поиск по описанию..."
                                value="{{ request('search') }}">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="voyager-search"></i> Найти
                        </button>

                        @if(request()->hasAny(['log_name', 'search', 'causer']))
                            <a href="{{ route('admin.activity-log') }}" class="btn btn-default" style="margin-left:5px;">
                                <i class="voyager-x"></i> Сбросить
                            </a>
                        @endif

                        <span class="text-muted" style="margin-left: 15px;">
                            Найдено: {{ $logs->total() }}
                        </span>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Таблица логов --}}
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-body" style="overflow-x: auto;">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 110px;">Группа</th>
                                <th>Действие</th>
                                <th style="width: 180px;">Пользователь</th>
                                <th style="width: 200px;">Объект</th>
                                <th style="width: 160px;">Дата</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="text-muted" style="font-size:12px;">{{ $log->id }}</td>

                                    <td>
                                        @php
                                            $badgeColors = [
                                                'auth'     => 'primary',
                                                'products' => 'success',
                                                'sellers'  => 'warning',
                                                'packages' => 'danger',
                                                'users'    => 'info',
                                                'system'   => 'default',
                                            ];
                                            $color = $badgeColors[$log->log_name] ?? 'default';
                                        @endphp
                                        <span class="badge badge-{{ $color }}" style="font-size:11px; padding: 4px 8px;">
                                            {{ $log->log_name }}
                                        </span>
                                    </td>

                                    <td>{{ $log->description }}</td>

                                    <td>
                                        @if ($log->causer)
                                            <small>
                                                <strong>{{ $log->causer->name }}</strong><br>
                                                <span class="text-muted">{{ $log->causer->email }}</span>
                                            </small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($log->subject)
                                            <small class="text-muted">
                                                {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                            </small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        <small class="text-muted" title="{{ $log->created_at }}">
                                            {{ $log->created_at->format('d.m.Y H:i:s') }}
                                        </small>
                                    </td>

                                    <td>
                                        <button type="button"
                                            class="btn btn-xs btn-default"
                                            data-toggle="modal"
                                            data-target="#log-modal-{{ $log->id }}"
                                            title="Подробнее">
                                            <i class="voyager-eye"></i>
                                        </button>
                                    </td>
                                </tr>

                                {{-- Модальное окно с деталями --}}
                                <div class="modal fade" id="log-modal-{{ $log->id }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                <h4 class="modal-title">
                                                    Запись #{{ $log->id }} &mdash;
                                                    <span class="badge badge-{{ $color }}">{{ $log->log_name }}</span>
                                                </h4>
                                            </div>
                                            <div class="modal-body">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th style="width:160px;">Действие</th>
                                                        <td>{{ $log->description }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Событие</th>
                                                        <td>{{ $log->event ?? '—' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Пользователь</th>
                                                        <td>
                                                            @if ($log->causer)
                                                                {{ $log->causer->name }} ({{ $log->causer->email }})
                                                                &mdash; ID: {{ $log->causer_id }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Объект</th>
                                                        <td>
                                                            @if ($log->subject_type)
                                                                {{ $log->subject_type }} #{{ $log->subject_id }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>IP</th>
                                                        <td>{{ $log->properties->get('ip') ?? '—' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Свойства</th>
                                                        <td>
                                                            @if ($log->properties && $log->properties->isNotEmpty())
                                                                <pre style="background:#f5f5f5; padding:10px; border-radius:4px; white-space:pre-wrap; word-break:break-all;">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Дата</th>
                                                        <td>{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted" style="padding: 40px;">
                                        <i class="voyager-list" style="font-size:40px; display:block; margin-bottom:10px;"></i>
                                        Записей не найдено
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Пагинация --}}
    @if ($logs->hasPages())
        <div class="row">
            <div class="col-md-12 text-center">
                {{ $logs->links() }}
            </div>
        </div>
    @endif

</div>
@stop
