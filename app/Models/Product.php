<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    
    protected $fillable = [
        'name',
        'sku', 
        'description',
        'instructions',
        'price',
        'stock',
        'category_id',
        'image_url',
    ];
    /**
     * Getter para la imagen principal
     */
    public function getMainImageAttribute()
    {
        return $this->image_url ?: 'storage/images/noImg.jpg';
    }
    /**
     * Relación polimórfica para imágenes adicionales (galería)
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imaginable');
    }
    /**
     * Relación con categoría
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
