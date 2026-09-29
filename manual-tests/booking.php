```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\BookingStatus;
use App\Exceptions\SeatBusMismatchException;
use App\Exceptions\SeatNotAvailableException;
use App\Models\Booking;
use App\Models\Bus;
use App\Models\Customer;
use App\Models\Trip;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;



function check(string $description, bool $condition): void
{
    if ($condition) {
        echo "PASS - {$description}" . PHP_EOL;
    } else {
        echo "FAIL - {$description}" . PHP_EOL;
        exit(1);
    }
}

echo "========================================" . PHP_EOL;
echo "      COMPLETE MANUAL BOOKING TEST" . PHP_EOL;
echo "========================================" . PHP_EOL;



$suffix = date('YmdHis');

$phone = '0599' . substr($suffix, -6);
$busNumber = 'MANUAL-BUS-' . $suffix;
$otherBusNumber = 'OTHER-BUS-' . $suffix;



$bookingService = app(BookingService::class);



echo PHP_EOL . "1. CREATE CUSTOMER" . PHP_EOL;

$customer = Customer::create([
    'name' => 'Manual Test Customer',
    'phone' => $phone,
]);

echo "Customer ID: {$customer->id}" . PHP_EOL;
echo "Name: {$customer->name}" . PHP_EOL;
echo "Phone: {$customer->phone}" . PHP_EOL;

check(
    'Customer was created successfully',
    $customer->exists
);


echo PHP_EOL . "2. CREATE BUS" . PHP_EOL;

$bus = Bus::create([
    'bus_number' => $busNumber,
    'capacity' => 3,
]);

echo "Bus ID: {$bus->id}" . PHP_EOL;
echo "Bus number: {$bus->bus_number}" . PHP_EOL;
echo "Capacity: {$bus->capacity}" . PHP_EOL;

check(
    'Bus was created successfully',
    $bus->exists
);


echo PHP_EOL . "3. CREATE SEATS" . PHP_EOL;

$seat1 = $bus->seats()->create([
    'seat_number' => '1',
]);

$seat2 = $bus->seats()->create([
    'seat_number' => '2',
]);

$seat3 = $bus->seats()->create([
    'seat_number' => '3',
]);

echo "Seat 1 ID: {$seat1->id}" . PHP_EOL;
echo "Seat 2 ID: {$seat2->id}" . PHP_EOL;
echo "Seat 3 ID: {$seat3->id}" . PHP_EOL;

$seatCount = $bus->seats()->count();

check(
    'Three seats were created for the bus',
    $seatCount === 3
);




echo PHP_EOL . "4. CREATE SECOND BUS AND FOREIGN SEAT" . PHP_EOL;

$otherBus = Bus::create([
    'bus_number' => $otherBusNumber,
    'capacity' => 1,
]);

$foreignSeat = $otherBus->seats()->create([
    'seat_number' => '1',
]);

echo "Other bus ID: {$otherBus->id}" . PHP_EOL;
echo "Foreign seat ID: {$foreignSeat->id}" . PHP_EOL;

check(
    'Second bus and foreign seat were created',
    $otherBus->exists && $foreignSeat->exists
);


echo PHP_EOL . "5. CREATE TRIP" . PHP_EOL;

$trip = Trip::create([
    'bus_id' => $bus->id,
    'departure_city' => 'Riyadh',
    'arrival_city' => 'Jeddah',
    'departure_at' => '2027-06-01 08:00:00',
]);

echo "Trip ID: {$trip->id}" . PHP_EOL;
echo "Route: {$trip->departure_city} -> {$trip->arrival_city}" . PHP_EOL;
echo "Departure: {$trip->departure_at}" . PHP_EOL;

check(
    'Trip was created successfully',
    $trip->exists
);



echo PHP_EOL . "6. CHECK INITIAL AVAILABLE SEATS" . PHP_EOL;

$availableSeats = $bookingService->getAvailableSeatsCount($trip);

echo "Available seats: {$availableSeats}" . PHP_EOL;

check(
    'Initial available seats are 3',
    $availableSeats === 3
);


echo PHP_EOL . "7. CREATE FIRST BOOKING" . PHP_EOL;

$booking1 = $bookingService->book(
    $customer->id,
    $trip->id,
    $seat1->id
);

echo "Booking ID: {$booking1->id}" . PHP_EOL;
echo "Customer ID: {$booking1->customer_id}" . PHP_EOL;
echo "Trip ID: {$booking1->trip_id}" . PHP_EOL;
echo "Seat ID: {$booking1->seat_id}" . PHP_EOL;
echo "Status: {$booking1->status->value}" . PHP_EOL;

check(
    'Booking was created successfully',
    $booking1->exists
);

check(
    'New booking has Pending status',
    $booking1->status === BookingStatus::Pending
);



echo PHP_EOL . "8. CHECK AVAILABLE SEATS AFTER BOOKING" . PHP_EOL;

$availableSeats = $bookingService->getAvailableSeatsCount($trip);

echo "Available seats: {$availableSeats}" . PHP_EOL;

check(
    'Available seats decreased from 3 to 2',
    $availableSeats === 2
);



echo PHP_EOL . "9. TRY TO BOOK THE SAME SEAT AGAIN" . PHP_EOL;

try {
    $bookingService->book(
        $customer->id,
        $trip->id,
        $seat1->id
    );

    check(
        'Double booking is rejected',
        false
    );
} catch (SeatNotAvailableException $e) {
    echo "Correctly rejected: seat is already booked." . PHP_EOL;

    check(
        'Double booking is rejected',
        true
    );
}



echo PHP_EOL . "10. CHECK AVAILABILITY AFTER REJECTED BOOKING" . PHP_EOL;

$availableSeats = $bookingService->getAvailableSeatsCount($trip);

echo "Available seats: {$availableSeats}" . PHP_EOL;

check(
    'Rejected booking did not change availability',
    $availableSeats === 2
);


echo PHP_EOL . "11. CREATE SECOND BOOKING" . PHP_EOL;

$booking2 = $bookingService->book(
    $customer->id,
    $trip->id,
    $seat2->id
);

echo "Booking ID: {$booking2->id}" . PHP_EOL;
echo "Seat ID: {$booking2->seat_id}" . PHP_EOL;
echo "Status: {$booking2->status->value}" . PHP_EOL;

check(
    'Second booking was created successfully',
    $booking2->exists
);

check(
    'Second booking has Pending status',
    $booking2->status === BookingStatus::Pending
);



echo PHP_EOL . "12. CHECK AVAILABLE SEATS" . PHP_EOL;

$availableSeats = $bookingService->getAvailableSeatsCount($trip);

echo "Available seats: {$availableSeats}" . PHP_EOL;

check(
    'Available seats decreased to 1',
    $availableSeats === 1
);



echo PHP_EOL . "13. TRY TO BOOK A SEAT FROM ANOTHER BUS" . PHP_EOL;

try {
    $bookingService->book(
        $customer->id,
        $trip->id,
        $foreignSeat->id
    );

    check(
        'Foreign bus seat is rejected',
        false
    );
} catch (SeatBusMismatchException $e) {
    echo "Correctly rejected: seat belongs to another bus." . PHP_EOL;

    check(
        'Foreign bus seat is rejected',
        true
    );
}



echo PHP_EOL . "14. VERIFY FOREIGN BOOKING WAS NOT CREATED" . PHP_EOL;

$foreignBookingExists = Booking::where('trip_id', $trip->id)
    ->where('seat_id', $foreignSeat->id)
    ->exists();

check(
    'No booking was created for the foreign seat',
    ! $foreignBookingExists
);



echo PHP_EOL . "15. CANCEL FIRST BOOKING" . PHP_EOL;

$cancelledBooking = $bookingService->cancel($booking1);

echo "Booking ID: {$cancelledBooking->id}" . PHP_EOL;
echo "Status: {$cancelledBooking->status->value}" . PHP_EOL;

check(
    'Booking status changed to Cancelled',
    $cancelledBooking->status === BookingStatus::Cancelled
);



echo PHP_EOL . "16. CHECK AVAILABLE SEATS AFTER CANCELLATION" . PHP_EOL;

$availableSeats = $bookingService->getAvailableSeatsCount($trip);

echo "Available seats: {$availableSeats}" . PHP_EOL;

check(
    'Cancelled seat became available again',
    $availableSeats === 2
);



echo PHP_EOL . "17. CHECK ACTIVE BOOKINGS" . PHP_EOL;

$activeBookingCount = Booking::active()
    ->where('trip_id', $trip->id)
    ->count();

echo "Active bookings: {$activeBookingCount}" . PHP_EOL;

check(
    'Only one active booking remains',
    $activeBookingCount === 1
);



echo PHP_EOL . "18. BOOK THE CANCELLED SEAT AGAIN" . PHP_EOL;

$booking3 = $bookingService->book(
    $customer->id,
    $trip->id,
    $seat1->id
);

echo "New booking ID: {$booking3->id}" . PHP_EOL;
echo "Seat ID: {$booking3->seat_id}" . PHP_EOL;
echo "Status: {$booking3->status->value}" . PHP_EOL;

check(
    'Cancelled seat can be booked again',
    $booking3->exists
);

check(
    'New booking has Pending status',
    $booking3->status === BookingStatus::Pending
);



echo PHP_EOL . "19. CANCEL THE SAME BOOKING TWICE" . PHP_EOL;

$bookingService->cancel($booking3);

$bookingService->cancel($booking3->fresh());

$finalBooking = $booking3->fresh();

echo "Final status: {$finalBooking->status->value}" . PHP_EOL;

check(
    'Cancelling an already cancelled booking is safe',
    $finalBooking->status === BookingStatus::Cancelled
);



echo PHP_EOL . "20. TRY BOOKING WITH NON-EXISTENT CUSTOMER" . PHP_EOL;

try {
    $bookingService->book(
        999999,
        $trip->id,
        $seat3->id
    );

    check(
        'Non-existent customer is rejected',
        false
    );
} catch (ModelNotFoundException $e) {
    echo "Correctly rejected: customer does not exist." . PHP_EOL;

    check(
        'Non-existent customer is rejected',
        true
    );
}



echo PHP_EOL . "21. TRY BOOKING WITH NON-EXISTENT TRIP" . PHP_EOL;

try {
    $bookingService->book(
        $customer->id,
        999999,
        $seat3->id
    );

    check(
        'Non-existent trip is rejected',
        false
    );
} catch (ModelNotFoundException $e) {
    echo "Correctly rejected: trip does not exist." . PHP_EOL;

    check(
        'Non-existent trip is rejected',
        true
    );
}



echo PHP_EOL . "22. TRY BOOKING WITH NON-EXISTENT SEAT" . PHP_EOL;

try {
    $bookingService->book(
        $customer->id,
        $trip->id,
        999999
    );

    check(
        'Non-existent seat is rejected',
        false
    );
} catch (ModelNotFoundException $e) {
    echo "Correctly rejected: seat does not exist." . PHP_EOL;

    check(
        'Non-existent seat is rejected',
        true
    );
}



echo PHP_EOL . "23. FINAL AVAILABILITY CHECK" . PHP_EOL;

$availableSeats = $bookingService->getAvailableSeatsCount($trip);

echo "Available seats: {$availableSeats}" . PHP_EOL;

check(
    'Final available seats are 2',
    $availableSeats === 2
);


echo PHP_EOL . "24. FINAL DATABASE CHECK" . PHP_EOL;

$customerExists = Customer::whereKey($customer->id)->exists();
$busExists = Bus::whereKey($bus->id)->exists();
$tripExists = Trip::whereKey($trip->id)->exists();

$booking1FromDatabase = Booking::find($booking1->id);
$booking2FromDatabase = Booking::find($booking2->id);
$booking3FromDatabase = Booking::find($booking3->id);

check(
    'Customer exists in MySQL',
    $customerExists
);

check(
    'Bus exists in MySQL',
    $busExists
);

check(
    'Trip exists in MySQL',
    $tripExists
);

check(
    'First booking is Cancelled in MySQL',
    $booking1FromDatabase?->status === BookingStatus::Cancelled
);

check(
    'Second booking is still Pending in MySQL',
    $booking2FromDatabase?->status === BookingStatus::Pending
);

check(
    'Third booking is Cancelled in MySQL',
    $booking3FromDatabase?->status === BookingStatus::Cancelled
);



echo PHP_EOL;
echo "========================================" . PHP_EOL;
echo "       ALL MANUAL TESTS PASSED" . PHP_EOL;
echo "========================================" . PHP_EOL;
echo "MySQL database: tripbooking" . PHP_EOL;
echo "Customer ID: {$customer->id}" . PHP_EOL;
echo "Bus ID: {$bus->id}" . PHP_EOL;
echo "Trip ID: {$trip->id}" . PHP_EOL;
echo "========================================" . PHP_EOL;
