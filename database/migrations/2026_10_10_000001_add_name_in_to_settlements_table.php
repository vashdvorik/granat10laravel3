<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Предложный падеж названия («Тирасполе») для SEO-заголовков: «купить в Тирасполе», а не «в Тирасполь».
 * Пусто — форма подбирается автоматически (`RussianLocative`), значение можно задать вручную в админке.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settlements', 'name_in')) {
            Schema::table('settlements', function (Blueprint $table): void {
                $table->string('name_in', 120)->nullable()->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('settlements', 'name_in')) {
            Schema::table('settlements', function (Blueprint $table): void {
                $table->dropColumn('name_in');
            });
        }
    }
};
