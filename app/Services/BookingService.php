<?php

namespace App\Services;

use App\Contracts\BookingServiceInterface;
use App\Enums\BookingStatus;
use App\Exceptions\SeatBusMismatchException;
use App\Exceptions\SeatNotAvailableException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Seat;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class BookingService implements BookingServiceInterface
{
    
    public function getAvailableSeatsCount(Trip $trip): int
    {
        $totalSeats = $trip->bus->seats()->count();

        $activeBookings = Booking::active()
            ->where('trip_id', $trip->id)
            ->count();

        return max(0, $totalSeats - $activeBookings);
    }

    
  


    public function book(int $customerId, int $tripId, int $seatId): Booking
{
    return DB::transaction(function () use ($customerId, $tripId, $seatId) {

        $customer = Customer::findOrFail($customerId);
        $trip = Trip::findOrFail($tripId);

      
        $seat = Seat::query()
            ->whereKey($seatId)
            ->lockForUpdate()
            ->firstOrFail();

       
        if ($seat->bus_id !== $trip->bus_id) {
            throw new SeatBusMismatchException($seatId, $tripId);
        }

        
        $alreadyBooked = Booking::active()
            ->where('trip_id', $tripId)
            ->where('seat_id', $seatId)
            ->exists();

        if ($alreadyBooked) {
            throw new SeatNotAvailableException($seatId, $tripId);
        }

        return Booking::create([
            'customer_id' => $customer->id,
            'trip_id' => $trip->id,
            'seat_id' => $seat->id,
            'status' => BookingStatus::Pending,
        ]);
    });
}


    public function cancel(Booking $booking): Booking
    {
        if ($booking->status === BookingStatus::Cancelled) {
          
            return $booking;
        }

        $booking->status = BookingStatus::Cancelled;
        $booking->save();

        return $booking;
    }
}
