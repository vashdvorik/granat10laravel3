<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Пользователь, пришедший через Google, не знает своего пароля (он случайный). Дата задания пароля
 * позволяет не требовать «текущий пароль» от такого пользователя и предложить ему задать пароль.
 * NULL = пароль пользователь не задавал.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'password_set_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('password_set_at')->nullable()->after('password');
            });
        }

        // Все существующие пользователи регистрировались с паролем
        DB::table('users')->whereNull('password_set_at')->update(['password_set_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'password_set_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_set_at');
            });
        }
    }
};
