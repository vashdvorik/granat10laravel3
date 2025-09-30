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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('path'); // Путь к изображению
            $table->string('alias')->nullable(); // Человекочитаемый URL
            $table->string('alt')->nullable(); // ALT текст для SEO
             $table->unsignedInteger('order')->default(0)->comment('Порядок сортировки');
            $table->timestamps();

            // Индексы для оптимизации
            $table->index('product_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
