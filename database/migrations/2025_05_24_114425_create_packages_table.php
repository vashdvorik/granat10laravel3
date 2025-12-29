<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('duration_days')->nullable()->comment('Количество дней действия пакета');

            // Лимиты товаров
            $table->integer('active_products_limit')->nullable()->comment('Лимит активных товаров (null = безлимит)');
            $table->integer('vip_products_limit')->nullable()->comment('Лимит VIP-товаров (null = безлимит)');

            // Функциональные возможности
             $table->boolean('has_website_link')->default(false)->comment('Размещение ссылки на сайт');
            $table->boolean('has_social_links')->default(false)->comment('Подключение соцсетей');
            $table->boolean('has_order_system')->default(false)->comment('Полноценный прием заказов');

            // активен ли пакет( для админа)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
