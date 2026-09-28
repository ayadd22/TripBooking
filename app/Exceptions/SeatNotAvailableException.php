<?php

namespace App\Exceptions;

use RuntimeException;


class SeatNotAvailableException extends RuntimeException
{
    public function __construct(int $seatId, int $tripId)
    {
        parent::__construct(
            "Seat {$seatId} is not available for trip {$tripId}."
        );
    }
}
