<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Pass extends Model
{
    protected $fillable = [
        'client_id',
        'serial_number',
        'authentication_token',
        'google_object_id',
        'content_updated_at',
        'last_pushed_at',
    ];

    protected $casts = [
        'content_updated_at' => 'datetime',
        'last_pushed_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Pass $pass) {
            $pass->serial_number ??= (string) Str::uuid();
            $pass->authentication_token ??= Str::random(40);
            $pass->content_updated_at ??= now();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(PassDevice::class);
    }

    /** Marca el contenido como cambiado (Apple detecta esto vía el web service). */
    public function touchContent(): void
    {
        $this->forceFill(['content_updated_at' => now()])->save();
    }
}
