<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasActiveScope;

    protected $fillable = [
        'customer_id',
        'trip_id',
        'seat_id',
        'status',
    ];

    
    protected array $activeStatuses = [
        BookingStatus::Pending->value,
        BookingStatus::Confirmed->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }
}
