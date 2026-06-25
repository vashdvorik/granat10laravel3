<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->ulid('public_id')->unique();
            $table->char('access_token_hash', 64);
            $table->char('idempotency_key_hash', 64)->unique();
            $table->foreignId('seller_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default(OrderStatus::New->value);
            $table->text('customer_name');
            $table->text('customer_phone');
            $table->char('customer_phone_hash', 64)->index();
            $table->unsignedSmallInteger('quantity');
            $table->string('fulfillment_method', 16);
            $table->text('delivery_address')->nullable();
            $table->text('customer_comment')->nullable();
            $table->string('product_name');
            $table->string('seller_name');
            $table->string('seller_phone')->nullable();
            $table->decimal('original_unit_price', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->text('delivery_terms')->nullable();
            $table->string('settlement_name')->nullable();
            $table->timestamp('consent_accepted_at');
            $table->string('consent_version', 32);
            $table->char('source_ip_hash', 64)->nullable();
            $table->timestamp('status_changed_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'created_at']);
            $table->index(['seller_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
