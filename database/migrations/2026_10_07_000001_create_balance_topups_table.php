<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Миграция идемпотентна: MySQL не откатывает DDL, поэтому после сбоя на внешнем ключе
        // таблица могла уже существовать. Повторный запуск должен её достроить, а не падать.
        if (! Schema::hasTable('balance_topups')) {
            Schema::create('balance_topups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('amount_cents');
                $table->string('currency', 3)->default('000');
                $table->string('status', 16)->default('pending')->index();
                $table->boolean('is_test')->default(false);
                $table->string('rrn', 32)->nullable();
                $table->string('card_last_digits', 4)->nullable();
                $table->unsignedTinyInteger('bank_state')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }

        // Внешний ключ необязателен: на части хостингов родительские таблицы (users) созданы не в InnoDB
        // или с другим регистром имени, и MySQL отвечает «Failed to open the referenced table».
        // Целостность в таком случае обеспечивает приложение; миграция при этом не должна падать.
        try {
            Schema::table('balance_topups', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        } catch (Throwable $e) {
            Log::warning('balance_topups: внешний ключ на users не создан: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_topups');
    }
};
