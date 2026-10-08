<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('social_accounts')) {
            Schema::create('social_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('provider', 32);
                $table->string('provider_user_id', 191);
                $table->string('email')->nullable();
                $table->timestamps();

                $table->unique(['provider', 'provider_user_id']);
            });
        }

        // Внешний ключ необязателен (см. create_balance_topups_table): на части хостингов
        // родительские таблицы не InnoDB, и MySQL не даёт создать ключ.
        try {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        } catch (Throwable $e) {
            Log::warning('social_accounts: внешний ключ на users не создан: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
