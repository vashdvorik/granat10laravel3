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
        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('duration_days')->nullable();
            $table->integer('active_products_limit')->nullable();
            $table->integer('vip_products_limit')->nullable();
            $table->boolean('has_website_link')->default(false);
            $table->boolean('has_social_links')->default(false);
            $table->boolean('has_order_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('seller_packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->index(['seller_id', 'is_active']);
            $table->index(['seller_id', 'is_active', 'end_date'], 'seller_packages_active_period_index');
            $table->timestamps();
        });

        Schema::table('sellers', function (Blueprint $table): void {
            $table->boolean('auto_renew')->default(true);
        });
    }
};
