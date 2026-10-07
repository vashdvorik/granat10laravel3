<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            // null при is_vip = true означает VIP без срока (выдан администратором)
            $table->timestamp('vip_until')->nullable()->after('is_vip');
            $table->index(['is_vip', 'vip_until'], 'products_vip_until_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_vip_until_index');
            $table->dropColumn('vip_until');
        });
    }
};
