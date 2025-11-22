<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'registration_id',
        'event_id',
        'amount',
        'status',
        'payment_method',
        'transaction_id',
        'paid_at',
        'refunded_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
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

