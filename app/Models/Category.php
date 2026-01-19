<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use HasFactory, NodeTrait;

    protected $fillable = [
        'name',
        'alias',
        'number',
        'desc',
        'icon',
        'icon_color',
        'bg_color',
        'parent_id',
    ];

    /*ДОБАВИЛ ПРИ ПЕРЕХОДЕ НА ПАКЕТ NESTED SET */
     /**
     * Получить breadcrumbs (хлебные крошки) для категории
     */
    public function getBreadcrumbsAttribute()
    {
        return $this->getAncestors()->push($this);
    }

    /**
     * Получить товары категории
     */
    public function products()
    {
        return $this->hasMany(\App\Models\Product::class);
    }
    
    /**
     * Получить все товары включая товары подкатегорий
     */
    public function allProducts()
    {
        $categoryIds = Category::whereDescendantOrSelf($this->id)->pluck('id');
        return \App\Models\Product::whereIn('category_id', $categoryIds);
    }

/* ОТКЛЮЧИЛ ПРИ ПЕРЕХОДЕ НА ПАКЕТ NESTED SET
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    } 

   
    public function getBreadcrumbsAttribute()
    {
        $breadcrumbs = collect();

        $category = $this;
        while ($category) {
            $breadcrumbs->prepend($category); // добавляем в начало коллекции
            $category = $category->parent;
        }

        return $breadcrumbs;
    }

    // Метод для получения товаров из категории
    public function products()
    {
    return $this->hasMany(\App\Models\Product::class);
    } */
}
