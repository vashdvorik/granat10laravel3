<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

class Category extends Model
{
    use HasFactory;

 /*   protected $table = 'category';*/

    protected $fillable = [
        'name',
        'alias',
        'number',
        'desc',
        'parent_id',
    ];

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Метод возвращает коллекцию родителей от корня до текущей категории
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
    }
}
