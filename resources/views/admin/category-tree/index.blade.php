@extends('voyager::master')

@section('page_title', 'Дерево категорий')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-list"></i> Дерево категорий
    </h1>
    <div class="page-header-actions" style="margin-top: 6px;">
        <button class="btn btn-sm btn-default" onclick="expandAll()">
            <i class="voyager-plus"></i> Развернуть все
        </button>
        <button class="btn btn-sm btn-default" style="margin-left: 5px;" onclick="collapseAll()">
            <i class="voyager-minus"></i> Свернуть все
        </button>
    </div>
@stop

@section('content')
<div class="page-content container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-body">

                    @if ($tree->isEmpty())
                        <p class="text-muted text-center" style="padding: 40px 0;">
                            Категорий пока нет
                        </p>
                    @else
                        @include('admin.category-tree._node', ['categories' => $tree])
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    /* Сброс стилей списка */
    .cat-tree-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .cat-tree-list .cat-tree-list {
        padding-left: 24px;
        border-left: 2px solid #e8e8e8;
        margin-left: 10px;
    }

    /* Строка категории */
    .cat-tree-item {
        margin: 0;
        padding: 0;
    }
    .cat-tree-row {
        display: flex;
        align-items: center;
        padding: 7px 10px;
        border-radius: 4px;
        transition: background 0.15s;
        user-select: none;
    }
    .has-children > .cat-tree-row {
        cursor: pointer;
    }
    .has-children > .cat-tree-row:hover {
        background: #f5f7fa;
    }

    /* Стрелка */
    .cat-toggle-icon {
        display: inline-block;
        width: 18px;
        font-size: 11px;
        color: #aaa;
        flex-shrink: 0;
        transition: transform 0.2s;
    }
    .cat-toggle-icon.cat-leaf {
        /* пустое место для выравнивания у листьев */
    }
    .cat-tree-row.collapsed .cat-toggle-icon {
        transform: rotate(-90deg);
    }

    /* Название */
    .cat-name {
        font-size: 14px;
        color: #333;
    }
    .cat-tree-list .cat-tree-list .cat-name {
        font-size: 13px;
        color: #555;
    }
    .cat-tree-list .cat-tree-list .cat-tree-list .cat-name {
        font-size: 12px;
        color: #777;
    }

    /* Бейдж с количеством товаров */
    .cat-badge {
        display: inline-block;
        margin-left: 8px;
        background: #e0e0e0;
        color: #555;
        border-radius: 10px;
        font-size: 11px;
        padding: 1px 8px;
        line-height: 1.6;
    }

    /* Дочерние — скрытие/показ */
    .cat-tree-children {
        overflow: hidden;
        transition: max-height 0.25s ease;
    }
    .cat-tree-children.hidden {
        display: none;
    }
</style>
@stop

@section('javascript')
<script>
    function toggleNode(rowEl) {
        var children = rowEl.closest('.cat-tree-item').querySelector('.cat-tree-children');
        if (!children) return;

        if (children.classList.contains('hidden')) {
            children.classList.remove('hidden');
            rowEl.classList.remove('collapsed');
        } else {
            children.classList.add('hidden');
            rowEl.classList.add('collapsed');
        }
    }

    function expandAll() {
        document.querySelectorAll('.cat-tree-children').forEach(function(el) {
            el.classList.remove('hidden');
        });
        document.querySelectorAll('.cat-tree-row').forEach(function(el) {
            el.classList.remove('collapsed');
        });
    }

    function collapseAll() {
        // Сворачиваем всё, кроме первого уровня
        document.querySelectorAll('.cat-tree-list .cat-tree-list .cat-tree-children').forEach(function(el) {
            el.classList.add('hidden');
        });
        document.querySelectorAll('.cat-tree-list .cat-tree-list .cat-tree-row').forEach(function(el) {
            el.classList.add('collapsed');
        });
    }
</script>
@stop
