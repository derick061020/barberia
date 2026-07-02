<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    public const TIERS = ['nuevo', 'frecuente', 'vip', 'premium'];

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'tier',
        'points',
        'status',
        'notes',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function pass(): HasOne
    {
        return $this->hasOne(Pass::class);
    }

    /** Etiqueta legible del nivel, para mostrar en el pase. */
    public function tierLabel(): string
    {
        return [
            'nuevo'     => 'Cliente Nuevo',
            'frecuente' => 'Cliente Frecuente',
            'vip'       => 'Cliente VIP',
            'premium'   => 'Membresía Premium',
        ][$this->tier] ?? ucfirst($this->tier);
    }
}
