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

        $categories = json_decode(file_get_contents($jsonPath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("❌ Ошибка JSON: " . json_last_error_msg());
            return;
        }

        $this->command->info("📥 Загружается категорий: " . count($categories));
        
        DB::beginTransaction();
        
        try {
            // Шаг 1: Создаем/обновляем все категории БЕЗ parent_id
            $this->command->info("⏳ Шаг 1/3: Создание/обновление категорий...");
            $created = 0;
            $updated = 0;
            
            foreach ($categories as $categoryData) {
                $exists = Category::where('id', $categoryData['id'])->exists();
                
                Category::updateOrCreate(
                    ['id' => $categoryData['id']], // Ключ для поиска
                    [
                        'name' => $categoryData['name'],
                        'alias' => $categoryData['alias'],
                        'number' => $categoryData['number'],
                        'desc' => $categoryData['desc'] ?? null,
                        'is_active' => $categoryData['is_active'] ?? true,
                        'parent_id' => null, // Временно NULL
                    ]
                );
                
                $exists ? $updated++ : $created++;
            }
            
            $this->command->info("   ✅ Создано: {$created}, обновлено: {$updated}");

            // Шаг 2: Связываем родителей по alias
            $this->command->info("⏳ Шаг 2/3: Связывание родителей...");
            $linked = 0;
            
            foreach ($categories as $categoryData) {
                if (!empty($categoryData['parent_alias'])) {
                    $parent = Category::where('alias', $categoryData['parent_alias'])->first();
                    
                    if ($parent) {
                        Category::where('id', $categoryData['id'])
                            ->update(['parent_id' => $parent->id]);
                        $linked++;
                    } else {
                        $this->command->warn("   ⚠️  Родитель '{$categoryData['parent_alias']}' не найден для категории ID {$categoryData['id']}");
                    }
                }
            }
            
            $this->command->info("   ✅ Связано категорий: {$linked}");

            // Шаг 3: Перестроение Nested Set
            $this->command->info("⏳ Шаг 3/3: Перестроение Nested Set дерева...");
            Category::fixTree();
            $this->command->info("   ✅ Дерево перестроено");
            
            DB::commit();
            
            $this->command->info("");
            $this->command->info("🎉 Успешно! Всего категорий в БД: " . Category::count());
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("❌ Ошибка: " . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }
}