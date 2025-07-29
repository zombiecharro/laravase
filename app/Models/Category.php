<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Scope: Buscar por slug exacto
     */
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Relación: Una categoría tiene muchos productos
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
