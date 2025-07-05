<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    protected $fillable = [
        'filename',
        'url',
        'type',
        'imaginable_id',
        'imaginable_type',
    ];

    protected $attributes = [
        'imaginable_id' => null,
        'imaginable_type' => null,
    ];

    /**
     * Relación polimórfica: Una imagen puede pertenecer a cualquier modelo
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
