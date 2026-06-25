<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductModerationAction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'product_moderation_run_id',
        'actor_user_id',
        'action',
        'reason_code',
        'comment',
        'previous_status',
        'new_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function moderationRun(): BelongsTo
    {
        return $this->belongsTo(ProductModerationRun::class, 'product_moderation_run_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
