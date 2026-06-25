<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductModerationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'content_version',
        'trigger',
        'provider',
        'model',
        'prompt_version',
        'status',
        'decision',
        'manual_decision',
        'agrees_with_manual',
        'compared_at',
        'risk_score',
        'categories',
        'reasons',
        'text_result',
        'image_result',
        'request_fingerprint',
        'provider_request_id',
        'latency_ms',
        'input_tokens',
        'output_tokens',
        'estimated_cost',
        'error_code',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'categories' => 'array',
        'reasons' => 'array',
        'text_result' => 'array',
        'image_result' => 'array',
        'risk_score' => 'decimal:4',
        'agrees_with_manual' => 'boolean',
        'compared_at' => 'datetime',
        'estimated_cost' => 'decimal:6',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
