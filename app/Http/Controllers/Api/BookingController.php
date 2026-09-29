<?php

namespace App\Http\Controllers\Api;

use App\Contracts\BookingServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    
    public function __construct(
        private readonly BookingServiceInterface $bookingService,
    ) {}

    
    public function availableSeats(Trip $trip): JsonResponse
    {
        $count = $this->bookingService->getAvailableSeatsCount($trip);

        return response()->json([
            'success' => true,
            'data'    => [
                'trip_id'         => $trip->id,
                'available_seats' => $count,
            ],
        ]);
    }

   
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->book(
            $request->integer('customer_id'),
            $request->integer('trip_id'),
            $request->integer('seat_id'),
        );

        return response()->json([
            'success' => true,
            'data'    => $booking,
        ], JsonResponse::HTTP_CREATED);
    }

    public function cancel(Booking $booking): JsonResponse
    {
        $booking = $this->bookingService->cancel($booking);

        return response()->json([
            'success' => true,
            'data'    => $booking,
        ]);
    }
}
