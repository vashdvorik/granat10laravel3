{{-- Полное дерево категорий для бокового меню. $menuRootCategories подставляет общий composer (AppServiceProvider). --}}
@include('components.menu-tree', ['items' => $menuRootCategories, 'level' => 1])
