<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_moderation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('content_version');
            $table->string('trigger', 50);
            $table->string('provider', 50)->nullable();
            $table->string('model')->nullable();
            $table->string('prompt_version', 50)->nullable();
            $table->string('status', 32)->index();
            $table->string('decision', 20)->nullable()->index();
            $table->decimal('risk_score', 5, 4)->nullable();
            $table->json('categories')->nullable();
            $table->json('reasons')->nullable();
            $table->json('text_result')->nullable();
            $table->json('image_result')->nullable();
            $table->string('request_fingerprint', 64)->index();
            $table->string('provider_request_id')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->decimal('estimated_cost', 12, 6)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['product_id', 'content_version', 'request_fingerprint'],
                'product_moderation_runs_version_fingerprint_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_moderation_runs');
    }
};
