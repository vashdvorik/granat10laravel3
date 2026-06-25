<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table): void {
            $table->boolean('delivery_enabled')->default(false)->after('can_order');
            $table->text('delivery_terms')->nullable()->after('delivery_enabled');
            $table->boolean('telegram_notifications_enabled')->default(false)->after('delivery_terms');
            $table->string('telegram_chat_id')->nullable()->after('telegram_notifications_enabled');
            $table->timestamp('telegram_verified_at')->nullable()->after('telegram_chat_id');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('can_order')->default(true)->after('publication_requested');
            $table->boolean('delivery_enabled')->default(true)->after('can_order');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['can_order', 'delivery_enabled']);
        });

        Schema::table('sellers', function (Blueprint $table): void {
            $table->dropColumn([
                'delivery_enabled',
                'delivery_terms',
                'telegram_notifications_enabled',
                'telegram_chat_id',
                'telegram_verified_at',
            ]);
        });
    }
};
