<?php

use App\Enums\ProductModerationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('moderation_status', 32)
                ->default(ProductModerationStatus::Draft->value)
                ->after('is_active')
                ->index();
            $table->unsignedInteger('moderation_version')->default(1)->after('moderation_status');
            $table->unsignedInteger('moderated_version')->nullable()->after('moderation_version');
            $table->string('moderation_source', 32)->nullable()->after('moderated_version');
            $table->decimal('moderation_risk_score', 5, 4)->nullable()->after('moderation_source');
            $table->text('moderation_summary')->nullable()->after('moderation_risk_score');
            $table->timestamp('moderation_submitted_at')->nullable()->after('moderation_summary');
            $table->timestamp('moderated_at')->nullable()->after('moderation_submitted_at');
            $table->foreignId('moderated_by_user_id')
                ->nullable()
                ->after('moderated_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('products')->update([
            'moderation_status' => ProductModerationStatus::Approved->value,
            'moderation_version' => 1,
            'moderated_version' => 1,
            'moderation_source' => 'legacy_migration',
            'moderation_summary' => 'Перенесён как ранее существовавший товар.',
            'moderated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by_user_id');
            $table->dropColumn([
                'moderation_status',
                'moderation_version',
                'moderated_version',
                'moderation_source',
                'moderation_risk_score',
                'moderation_summary',
                'moderation_submitted_at',
                'moderated_at',
            ]);
        });
    }
};
