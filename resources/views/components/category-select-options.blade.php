{{-- для списка категорий в бекенд --}}
@foreach($categories as $category)
    <option value="{{ $category->id }}" 
            {{ $selected == $category->id ? 'selected' : '' }}
            {{ $category->children->isNotEmpty() ? 'disabled' : '' }}>
        {{ str_repeat('—', $category->depth) }} {{ $category->name }}
        {{ $category->children->isNotEmpty() ? ' (выберите подкатегорию)' : '' }}
    </option>
    
    @if($category->children->isNotEmpty())
        @include('components.category-select-options', [
            'categories' => $category->children,
            'selected' => $selected
        ])
    @endif
@endforeach