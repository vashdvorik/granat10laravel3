<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'lowprice',
        'desc',
        'features1',
        'features2',
        'features3',
        'features4',
        'alias',
        'is_vip',
        'is_active',
        'category_id',
    ];
    protected $guarded = [
    'id',
    'seller_id',      // Только код устанавливает владельца
    'sale_started_at', // Автоматически при активации акции
    'push_at',        // Автоматически при продвижении
];
    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function seller()
    {
        return $this->belongsTo(\App\Models\Seller::class);
    }
    
    public function images()
    {
        return $this->hasMany(\App\Models\Image::class);
    }   

}
