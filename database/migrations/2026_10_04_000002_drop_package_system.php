<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Полное удаление системы пакетов (тарифов).
 * Денежный баланс продавца (users.balance) не затрагивается.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Старые записи журнала сохраняем, но отвязываем от удаляемых моделей,
        // иначе админка не сможет загрузить связанный объект несуществующего класса.
        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->whereIn('subject_type', ['App\\Models\\Package', 'App\\Models\\SellerPackage'])
                ->update(['subject_type' => null, 'subject_id' => null]);
        }

        Schema::dropIfExists('seller_packages');
        Schema::dropIfExists('packages');

        if (Schema::hasColumn('sellers', 'auto_renew')) {
            Schema::table('sellers', function (Blueprint $table): void {
                $table->dropColumn('auto_renew');
            });
        }
    }

    public function down(): void
    {
        // Удаление тарифов необратимо: данные таблиц packages и seller_packages не сохраняются.
        // Пустые таблицы всё равно ничего не вернут, а частичный откат оставляет базу в
        // неконсистентном состоянии (так уже случалось на сервере), поэтому откат запрещён.
        // Для возврата к тарифам восстанавливайте базу из резервной копии.
        throw new RuntimeException('Миграция drop_package_system необратима: восстановите базу из резервной копии.');
    }
};
