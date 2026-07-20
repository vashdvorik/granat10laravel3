<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_moderation_runs', function (Blueprint $table) {
            $table->dropUnique('product_moderation_runs_version_provider_unique');
            $table->foreignId('retry_of_id')->nullable()->after('content_version')
                ->constrained('product_moderation_runs')->nullOnDelete();
            $table->unsignedInteger('attempt_number')->default(1)->after('retry_of_id');
            $table->string('configuration_version', 64)->nullable()->after('attempt_number')->index();
            $table->unique(
                ['product_id', 'content_version', 'request_fingerprint', 'provider', 'attempt_number'],
                'moderation_runs_provider_attempt_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_moderation_runs', function (Blueprint $table) {
            $table->dropUnique('moderation_runs_provider_attempt_unique');
            $table->dropForeign(['retry_of_id']);
            $table->dropIndex(['configuration_version']);
            $table->dropColumn(['retry_of_id', 'attempt_number', 'configuration_version']);
            $table->unique(
                ['product_id', 'content_version', 'request_fingerprint', 'provider'],
                'product_moderation_runs_version_provider_unique'
            );
        });
    }
};
