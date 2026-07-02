<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassDevice extends Model
{
    protected $fillable = [
        'pass_id',
        'device_library_identifier',
        'push_token',
    ];

    public function pass(): BelongsTo
    {
        return $this->belongsTo(Pass::class);
    }
}
