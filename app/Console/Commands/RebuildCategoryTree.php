<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;

class RebuildCategoryTree extends Command
{
    protected $signature = 'categories:rebuild-tree';
    protected $description = 'Rebuild nested set tree for categories';

    public function handle()
    {
        $this->info('Rebuilding category tree...');
        
        // Пересобрать дерево на основе parent_id
        Category::fixTree();
        
        $this->info('✓ Category tree rebuilt successfully!');
        
        // Показать статистику
        $total = Category::count();
        $roots = Category::whereIsRoot()->count();
        
        $this->info("Total categories: {$total}");
        $this->info("Root categories: {$roots}");
        
        return 0;
    }
}
