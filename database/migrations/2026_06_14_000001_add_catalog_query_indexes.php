<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index(
                ['category_id', 'is_active', 'moderation_status', 'is_vip', 'push_at'],
                'products_catalog_category_visibility_sort_index'
            );
            $table->index(
                ['seller_id', 'is_active', 'is_vip'],
                'products_seller_active_vip_index'
            );
            $table->index(
                ['seller_id', 'created_at'],
                'products_seller_created_index'
            );
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->index(
                ['parent_id', 'is_active', 'number'],
                'categories_parent_active_number_index'
            );
        });

        Schema::table('seller_packages', function (Blueprint $table): void {
            $table->index(
                ['seller_id', 'is_active', 'end_date'],
                'seller_packages_active_period_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_catalog_category_visibility_sort_index');
            $table->dropIndex('products_seller_active_vip_index');
            $table->dropIndex('products_seller_created_index');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex('categories_parent_active_number_index');
        });

        Schema::table('seller_packages', function (Blueprint $table): void {
            $table->dropIndex('seller_packages_active_period_index');
        });
    }
};
