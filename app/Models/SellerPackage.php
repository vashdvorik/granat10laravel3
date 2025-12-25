<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'package_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Связь с продавцом
     */
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
    /**
     * Связь с пакетом
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
