<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;

class ExportCategoriesToJson extends Command
{
    protected $signature = 'categories:export';
    protected $description = 'Экспорт категорий из БД в JSON файл';

    public function handle()
    {
        $this->info("📤 Экспорт категорий из базы данных...");
        
        $categories = Category::orderBy('id')
            ->get(['id', 'name', 'alias', 'number', 'desc', 'parent_id', 'is_active'])
            ->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'alias' => $category->alias,
                    'number' => $category->number,
                    'desc' => $category->desc,
                    'parent_alias' => $category->parent ? $category->parent->alias : null,
                    'is_active' => (bool) $category->is_active
                ];
            });

        $jsonPath = database_path('seeders/categories.json');
        $json = json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        file_put_contents($jsonPath, $json);
        
        $this->info("✅ Экспортировано категорий: " . $categories->count());
        $this->info("📁 Файл: {$jsonPath}");
    }
}