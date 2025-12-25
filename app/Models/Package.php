<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'active_products_limit',
        'vip_products_limit',
        'is_active',
    ];

    public function sellerPackages()
    {
        return $this->hasMany(SellerPackage::class);
    }
}