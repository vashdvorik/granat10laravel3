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
        Schema::create('seller_packages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seller_id')->constrained();
            $table->foreignId('package_id')->constrained();
            $table->date('start_date')->comment('Дата начала действия пакета');
            $table->date('end_date')->comment('Дата окончания действия пакета');
            $table->boolean('is_active')->default(true)->comment('Активен ли пакет');

            // Индекс для быстрого поиска по продавцу и активности
            $table->index(['seller_id', 'is_active']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_packages');
    }
};
