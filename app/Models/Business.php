<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Business extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'description',
        'address',
        'latitude',
        'longitude',
        'proximity_radius',
        'proximity_message',
        'background_color',
        'foreground_color',
        'label_color',
    ];

    protected $casts = [
        'latitude'         => 'float',
        'longitude'        => 'float',
        'proximity_radius' => 'integer',
    ];

    /** ¿Tiene coordenadas configuradas para las notificaciones por proximidad? */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /** Mensaje de proximidad con un texto por defecto si no se configuró. */
    public function proximityText(): string
    {
        return $this->proximity_message
            ?: '¡Estás cerca de ' . $this->name . '! Pasa a visitarnos 💈';
    }

    protected static function booted(): void
    {
        static::creating(function (Business $business) {
            if (empty($business->slug)) {
                $business->slug = Str::slug($business->name) . '-' . Str::lower(Str::random(4));
            }
        });
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }
}
