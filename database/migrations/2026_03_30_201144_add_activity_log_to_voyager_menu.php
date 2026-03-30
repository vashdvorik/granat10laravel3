<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menuId = DB::table('menus')->where('name', 'admin')->value('id');

        if (!$menuId) {
            return;
        }

        $exists = DB::table('menu_items')
            ->where('menu_id', $menuId)
            ->where('route', 'admin.activity-log')
            ->exists();

        if (!$exists) {
            DB::table('menu_items')->insert([
                'menu_id'    => $menuId,
                'title'      => 'Журнал действий',
                'url'        => '',
                'route'      => 'admin.activity-log',
                'target'     => '_self',
                'icon_class' => 'voyager-list',
                'color'      => null,
                'parent_id'  => null,
                'order'      => 99,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $menuId = DB::table('menus')->where('name', 'admin')->value('id');

        if ($menuId) {
            DB::table('menu_items')
                ->where('menu_id', $menuId)
                ->where('route', 'admin.activity-log')
                ->delete();
        }
    }
};
