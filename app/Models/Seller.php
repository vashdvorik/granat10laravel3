<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'town',
        'sellerplace',
        'address',
        'description',
        'alias',
        'product_count',
        'region_id',
        'link',
        'link_map',
        'telegram',
        'instagram',
        'viber',
        'photos',
        'logo',
        'balance',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    } 
}
