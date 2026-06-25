<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_moderation_runs', function (Blueprint $table) {
            $table->index('product_id', 'product_moderation_runs_product_id_index');
        });

        Schema::table('product_moderation_runs', function (Blueprint $table) {
            $table->dropUnique('product_moderation_runs_version_fingerprint_unique');
            $table->unique(
                ['product_id', 'content_version', 'request_fingerprint', 'provider'],
                'product_moderation_runs_version_provider_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_moderation_runs', function (Blueprint $table) {
            $table->dropUnique('product_moderation_runs_version_provider_unique');
            $table->unique(
                ['product_id', 'content_version', 'request_fingerprint'],
                'product_moderation_runs_version_fingerprint_unique'
            );
            $table->dropIndex('product_moderation_runs_product_id_index');
        });
    }
};
