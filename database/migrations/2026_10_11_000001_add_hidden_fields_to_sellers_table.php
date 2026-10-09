<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «Скрыть продавца»: администратор снимает магазин и все его товары с публичного сайта и пишет причину.
 * Продавец видит причину красным блоком в кабинете. Пустой `hidden_at` — магазин показывается как обычно.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sellers', 'hidden_at')) {
            return;
        }

        Schema::table('sellers', function (Blueprint $table): void {
            $table->timestamp('hidden_at')->nullable();
            $table->text('hidden_reason')->nullable();
            $table->foreignId('hidden_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sellers', 'hidden_at')) {
            return;
        }

        Schema::table('sellers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('hidden_by_user_id');
            $table->dropColumn(['hidden_at', 'hidden_reason']);
        });
    }
};
