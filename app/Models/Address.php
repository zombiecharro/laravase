<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'street',
        'street_number',
        'apartment',
        'city',
        'state',
        'postal_code',
        'country',
        'additional_info',
        'is_default',
        'is_temporary',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_temporary' => 'boolean',
    ];

    /**
     * Relación: Una dirección pertenece a un usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Una dirección puede tener muchas órdenes
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope para obtener la dirección por defecto
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope para obtener direcciones temporales
     */
    public function scopeTemporary($query)
    {
        return $query->where('is_temporary', true);
    }

    /**
     * Scope para obtener direcciones permanentes (de usuario)
     */
    public function scopePermanent($query)
    {
        return $query->where('is_temporary', false);
    }

    /**
     * Formato completo de la dirección
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->street_number,
            $this->apartment,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
