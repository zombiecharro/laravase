<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'bio',
        'profile_picture',
        'birth_date',
        'preferences',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'preferences' => 'array',
    ];

    /**
     * Relación: Un perfil pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
