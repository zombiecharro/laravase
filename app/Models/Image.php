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
        'imageable_id',
        'imageable_type',
    ];

    protected $attributes = [
        'imageable_id' => null,
        'imageable_type' => null,
    ];

    /**
     * Relación polimórfica: Una imagen puede pertenecer a cualquier modelo
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
