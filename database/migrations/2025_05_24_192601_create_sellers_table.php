<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();        

            // Связь с users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Основная информация
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('town')->nullable();
            $table->string('sellerplace')->nullable(); //торговый район
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('alias')->unique();
            $table->integer('product_count')->default(0); // Количество товаров у продавца
            // Ссылки и контакты
            $table->string('link')->nullable(); // Ссылка на сайт
            $table->text('link_map')->nullable(); // Ссылка на карту
            $table->text('telegram')->nullable();
            $table->text('instagram')->nullable();
            $table->text('viber')->nullable();
            
            //фотографии
            $table->text('photos')->nullable()->comment('Фото магазина 442');
            $table->string('photo')->nullable()->comment('Фото магазина 1040');
            
            // Баланс
            $table->decimal('balance', 10, 2)->default(0)->comment('Баланс для оплаты подписок и услуг');
            
            //Может ли принимать заказы
            $table->boolean('can_order')->default(false)->comment('Может ли продавец принимать заказы');

            // Таймстампы и индексы
            $table->timestamps();
            $table->index('alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};