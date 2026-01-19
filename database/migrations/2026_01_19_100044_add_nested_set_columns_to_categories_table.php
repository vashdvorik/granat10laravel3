<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Nested Set поля
            $table->unsignedInteger('_lft')->default(0)->after('parent_id');
            $table->unsignedInteger('_rgt')->default(0)->after('_lft');
            
            // Индексы для быстрого поиска
            $table->index(['_lft', '_rgt']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['_lft', '_rgt']);
            $table->dropColumn(['_lft', '_rgt']);
        });
    }
};