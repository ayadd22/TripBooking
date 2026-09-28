<?php

namespace App\Contracts;

use App\Models\Booking;
use App\Models\Trip;


interface BookingServiceInterface
{
  
    public function getAvailableSeatsCount(Trip $trip): int;

  
    public function isSeatAvailable(Trip $trip, int $seatId): bool;

   
    public function book(int $customerId, int $tripId, int $seatId): Booking;

   
    public function cancel(Booking $booking): Booking;
}
