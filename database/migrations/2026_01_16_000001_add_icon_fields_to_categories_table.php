<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('desc');
            $table->string('icon_color')->default('#696969')->after('icon'); // цвет иконки
            $table->string('bg_color')->default('#e6e6e6')->after('icon_color'); // цвет фона
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['icon', 'icon_color', 'bg_color']);
        });
    }
};