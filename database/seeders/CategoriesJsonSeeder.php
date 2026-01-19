<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoriesJsonSeeder extends Seeder
{
    /**
     * 🎯 УМНЫЙ СИДЕР ДЛЯ КАТЕГОРИЙ
     * 
     * Особенности:
     * - ID категорий фиксированы (не меняются при повторном запуске)
     * - Alias не меняется (URL стабильны)
     * - Товары остаются в своих категориях
     * - Можно добавлять новые категории в JSON и запускать сидер
     * - Можно редактировать существующие (name, desc) без потери связей
     * - Связи через parent_alias вместо parent_id (удобнее)
     * 
     * Использование:
     * php artisan db:seed --class=CategoriesJsonSeeder
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/categories.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error("❌ Файл categories.json не найден!");
            $this->command->info("📍 Ожидается: {$jsonPath}");
            return;
        }

        $json = file_get_contents($jsonPath);
        $categories = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("❌ Ошибка JSON: " . json_last_error_msg());
            return;
        }

        $this->command->info("📥 Загружается категорий: " . count($categories));
        
        DB::beginTransaction();
        
        try {
            $this->processCategories($categories);
            
            Category::fixTree();
            
            $this->command->info('✅ Категории успешно импортированы из JSON');
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Ошибка: ' . $e->getMessage());
            throw $e;
        }
    }
    /*
    private function processCategories(array $categories, $parentId = null): void
    {
        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);
            
            $exists = Category::where('id', $categoryData['id'])->exists();
            
            $category = Category::updateOrCreate(
                ['id' => $categoryData['id']],
                array_merge($categoryData, ['parent_id' => $parentId])
            );
            
            $action = $exists ? '♻️  Обновлена' : '✨ Создана';
            $this->command->info("$action: ID {$category->id} - {$category->name}");
            
            if (!empty($children)) {
                $this->processCategories($children, $category->id);
            }
        }
    }*/
    private function processCategories(array $categories, $parentId = null): void
    {
        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);
            
            // 1. Принудительно добавляем parent_id в массив данных
            $categoryData['parent_id'] = $parentId;

            // 2. Используем updateOrCreate, чтобы сохранить фиксированный ID
            // Но после сохранения МЫ НЕ вызываем fixTree в цикле (это медленно)
            $category = Category::updateOrCreate(
                ['id' => $categoryData['id']],
                $categoryData
            );

            $action = $category->wasRecentlyCreated ? '✨ Создана' : '♻️ Обновлена';
            $this->command->info("$action: ID {$category->id} - {$category->name}");

            if (!empty($children)) {
                // Рекурсивно идем глубже
                $this->processCategories($children, $category->id);
            }
        }
    }
}