<?php

namespace App\Exceptions;

use RuntimeException;

class SeatBusMismatchException extends RuntimeException
{
    public function __construct(int $seatId, int $tripId)
    {
        parent::__construct(
            "Seat {$seatId} does not belong to the bus assigned to trip {$tripId}."
        );
    }
}
