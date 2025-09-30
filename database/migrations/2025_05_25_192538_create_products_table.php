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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->decimal('lowprice', 10, 2)->nullable();
                $table->text('desc')->nullable();
                $table->string('features1')->nullable();
                $table->string('features2')->nullable();
                $table->string('features3')->nullable();
                $table->string('features4')->nullable();
                $table->string('alias')->unique();
                //Вип товара
                $table->boolean('is_vip')->default(false);
                //Продается ли товар
                $table->boolean('is_active')->default(true);
                // Начало скидки
                $table->timestamp('sale_started_at')->nullable();
                // Поднятие товаров в списке
                $table->timestamp('push_at')->nullable();
                
            $table->timestamps();

            // Добавляем составной индекс
            $table->index(['is_active', 'is_vip', 'push_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
