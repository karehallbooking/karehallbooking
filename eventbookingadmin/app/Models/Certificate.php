<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'registration_id',
        'event_id',
        'file_path',
        'is_revoked',
        'revoked_at',
    ];

    protected $casts = [
        'is_revoked' => 'boolean',
        'revoked_at' => 'datetime',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

