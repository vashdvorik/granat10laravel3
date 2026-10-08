<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Покупки VIP-размещения: сколько заплачено, за какой период. Нужны для истории, для расчёта
 * возврата при досрочном отключении и для отчётов в админке.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vip_purchases')) {
            Schema::create('vip_purchases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedInteger('days');
                $table->unsignedBigInteger('price_cents');
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->string('status', 16)->default('active'); // active | cancelled
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('refunded_cents')->default(0);
                $table->timestamps();

                $table->index(['product_id', 'status']);
                $table->index('ends_at');
            });
        }

        try {
            Schema::table('vip_purchases', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        } catch (Throwable $e) {
            Log::warning('vip_purchases: внешние ключи не созданы: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_purchases');
    }
};
