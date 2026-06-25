<?php

use App\Enums\ProductModerationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->where('moderation_status', ProductModerationStatus::Failed->value)
            ->update([
                'moderation_status' => ProductModerationStatus::NeedsReview->value,
                'moderation_summary' => 'Товар передан администратору для дополнительной проверки.',
                'is_active' => false,
            ]);
    }

    public function down(): void
    {
    }
};
