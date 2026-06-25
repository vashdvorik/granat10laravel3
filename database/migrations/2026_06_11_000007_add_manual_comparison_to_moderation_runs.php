<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_moderation_runs', function (Blueprint $table) {
            $table->string('manual_decision', 20)->nullable()->index()->after('decision');
            $table->boolean('agrees_with_manual')->nullable()->index()->after('manual_decision');
            $table->timestamp('compared_at')->nullable()->after('agrees_with_manual');
        });
    }

    public function down(): void
    {
        Schema::table('product_moderation_runs', function (Blueprint $table) {
            $table->dropColumn(['manual_decision', 'agrees_with_manual', 'compared_at']);
        });
    }
};
