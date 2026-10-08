<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Единый журнал баланса: каждая операция, меняющая users.balance, пишет сюда строку
 * (сумма со знаком, баланс после операции, понятное описание, ссылка на источник).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('balance_transactions')) {
            Schema::create('balance_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('type', 32);
                $table->bigInteger('amount_cents');
                $table->bigInteger('balance_after_cents');
                $table->string('description', 255);
                $table->string('reference_type', 64)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index('type');
                $table->index(['reference_type', 'reference_id']);
            });
        }

        try {
            Schema::table('balance_transactions', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        } catch (Throwable $e) {
            Log::warning('balance_transactions: внешний ключ на users не создан: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
    }
};
