<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ИИ-модерация работает только через OpenRouter: зашифрованные ключи прежних сервисов
 * (Google Gemini, DeepSeek, OpenAI и др.) больше не используются, поэтому не храним их в базе.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')
            ->where('key', 'like', 'moderation.secret.text.%')
            ->orWhere('key', 'like', 'moderation.secret.image.%')
            ->delete();
    }

    public function down(): void
    {
        // Удалённые ключи восстановить нельзя; при откате вводить ключ OpenRouter заново не требуется.
    }
};
