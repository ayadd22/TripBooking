<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class BookingScenarioTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createCustomer(string $name = 'Ahmed Ali', string $phone = '0512345678'): int
    {
        return $this->postJson('/api/customers', compact('name', 'phone'))
                    ->assertStatus(201)
                    ->json('data.id');
    }

    private function createBus(string $busNumber = 'BUS-001', int $capacity = 3): int
    {
        return $this->postJson('/api/buses', ['bus_number' => $busNumber, 'capacity' => $capacity])
                    ->assertStatus(201)
                    ->json('data.id');
    }

    private function createSeat(int $busId, string $seatNumber): int
    {
        return $this->postJson("/api/buses/{$busId}/seats", ['seat_number' => $seatNumber])
                    ->assertStatus(201)
                    ->json('data.id');
    }

    private function createTrip(int $busId): int
    {
        return $this->postJson('/api/trips', [
            'bus_id'         => $busId,
            'departure_city' => 'Riyadh',
            'arrival_city'   => 'Jeddah',
            'departure_at'   => '2027-06-01 08:00:00',
        ])->assertStatus(201)->json('data.id');
    }

    // -------------------------------------------------------------------------
    // Main scenario — all 8 steps in a single flowing test
    // -------------------------------------------------------------------------

    public function test_complete_booking_workflow(): void
    {
        // ── Setup ─────────────────────────────────────────────────────────────
        $customerId = $this->createCustomer();
        $busId      = $this->createBus('SC-BUS', 3);
        $seat1Id    = $this->createSeat($busId, '1');
        $seat2Id    = $this->createSeat($busId, '2');
        $seat3Id    = $this->createSeat($busId, '3');
        $tripId     = $this->createTrip($busId);

     
        $this->getJson("/api/trips/{$tripId}/available-seats")
             ->assertStatus(200)
             ->assertJsonPath('success', true)
             ->assertJsonPath('data.trip_id', $tripId)
             ->assertJsonPath('data.available_seats', 3);

   
        $booking1Response = $this->postJson('/api/bookings', [
            'customer_id' => $customerId,
            'trip_id'     => $tripId,
            'seat_id'     => $seat1Id,
        ]);

        $booking1Response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', BookingStatus::Pending->value)
            ->assertJsonPath('data.trip_id', $tripId)
            ->assertJsonPath('data.seat_id', $seat1Id)
            ->assertJsonPath('data.customer_id', $customerId);

        $booking1Id = $booking1Response->json('data.id');

        $this->assertDatabaseHas('bookings', [
            'id'          => $booking1Id,
            'seat_id'     => $seat1Id,
            'trip_id'     => $tripId,
            'status'      => BookingStatus::Pending->value,
        ]);

        $this->getJson("/api/trips/{$tripId}/available-seats")
             ->assertStatus(200)
             ->assertJsonPath('data.available_seats', 2);

       
        $doubleBook = $this->postJson('/api/bookings', [
            'customer_id' => $customerId,
            'trip_id'     => $tripId,
            'seat_id'     => $seat1Id,
        ]);

        $doubleBook
            ->assertStatus(422)
            ->assertJsonPath('success', false);

       
        $this->assertNotEmpty($doubleBook->json('message'));

    
        $this->assertSame(1, Booking::active()
            ->where('trip_id', $tripId)
            ->where('seat_id', $seat1Id)
            ->count()
        );

      
        $this->getJson("/api/trips/{$tripId}/available-seats")
             ->assertJsonPath('data.available_seats', 2);

     
        $this->postJson('/api/bookings', [
            'customer_id' => $customerId,
            'trip_id'     => $tripId,
            'seat_id'     => $seat2Id,
        ])->assertStatus(201)
          ->assertJsonPath('data.status', BookingStatus::Pending->value);

      
        $this->getJson("/api/trips/{$tripId}/available-seats")
             ->assertJsonPath('data.available_seats', 1);

       
        $this->patchJson("/api/bookings/{$booking1Id}/cancel")
             ->assertStatus(200)
             ->assertJsonPath('success', true)
             ->assertJsonPath('data.status', BookingStatus::Cancelled->value);

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking1Id,
            'status' => BookingStatus::Cancelled->value,
        ]);

      
        $this->getJson("/api/trips/{$tripId}/available-seats")
             ->assertStatus(200)
             ->assertJsonPath('data.available_seats', 2);

      
        $this->assertSame(0, Booking::active()
            ->where('trip_id', $tripId)
            ->where('seat_id', $seat1Id)
            ->count()
        );

      
        $this->postJson('/api/bookings', [
            'customer_id' => $customerId,
            'trip_id'     => $tripId,
            'seat_id'     => $seat1Id,
        ])->assertStatus(201)
          ->assertJsonPath('data.status', BookingStatus::Pending->value);

      
        $this->getJson("/api/trips/{$tripId}/available-seats")
             ->assertJsonPath('data.available_seats', 1);
    }

    // -------------------------------------------------------------------------
    // Additional business-rule checks
    // -------------------------------------------------------------------------

    public function test_seat_from_another_bus_cannot_be_booked(): void
    {
        $customerId = $this->createCustomer('Sara', '0511111111');

        
        $busAId  = $this->createBus('BUS-A', 2);
        $seat1Id = $this->createSeat($busAId, '1');
        $tripId  = $this->createTrip($busAId);

      
        $busBId      = $this->createBus('BUS-B', 1);
        $foreignSeat = $this->createSeat($busBId, '1');

      
        $this->postJson('/api/bookings', [
            'customer_id' => $customerId,
            'trip_id'     => $tripId,
            'seat_id'     => $foreignSeat,
        ])->assertStatus(422)
          ->assertJsonPath('success', false);

       
        $this->assertDatabaseMissing('bookings', [
            'trip_id' => $tripId,
            'seat_id' => $foreignSeat,
        ]);
    }

    public function test_nonexistent_customer_id_returns_422(): void
    {
        $busId  = $this->createBus('NEC-BUS');
        $seatId = $this->createSeat($busId, '1');
        $tripId = $this->createTrip($busId);

        $this->postJson('/api/bookings', [
            'customer_id' => 999999,
            'trip_id'     => $tripId,
            'seat_id'     => $seatId,
        ])->assertStatus(422)
          ->assertJsonStructure(['errors' => ['customer_id']]);
    }

    public function test_nonexistent_trip_id_in_booking_request_returns_422(): void
    {
        $customerId = $this->createCustomer('Ali', '0522222222');
        $busId      = $this->createBus('NET-BUS');
        $seatId     = $this->createSeat($busId, '1');

        $this->postJson('/api/bookings', [
            'customer_id' => $customerId,
            'trip_id'     => 999999,
            'seat_id'     => $seatId,
        ])->assertStatus(422)
          ->assertJsonStructure(['errors' => ['trip_id']]);
    }

    public function test_nonexistent_seat_id_in_booking_request_returns_422(): void
    {
        $customerId = $this->createCustomer('Fatima', '0533333333');
        $busId      = $this->createBus('NES-BUS');
        $tripId     = $this->createTrip($busId);

        $this->postJson('/api/bookings', [
            'customer_id' => $customerId,
            'trip_id'     => $tripId,
            'seat_id'     => 999999,
        ])->assertStatus(422)
          ->assertJsonStructure(['errors' => ['seat_id']]);
    }

    public function test_nonexistent_trip_in_available_seats_route_returns_404(): void
    {
        $this->getJson('/api/trips/999999/available-seats')
             ->assertStatus(404)
             ->assertJsonPath('success', false);
    }

    public function test_nonexistent_booking_in_cancel_route_returns_404(): void
    {
        $this->patchJson('/api/bookings/999999/cancel')
             ->assertStatus(404)
             ->assertJsonPath('success', false);
    }

    public function test_cancelling_already_cancelled_booking_is_idempotent(): void
    {
        $customerId = $this->createCustomer('Khalid', '0544444444');
        $busId      = $this->createBus('IDEM-BUS');
        $seatId     = $this->createSeat($busId, '1');
        $tripId     = $this->createTrip($busId);

        $bookingId = $this->postJson('/api/bookings', [
            'customer_id' => $customerId,
            'trip_id'     => $tripId,
            'seat_id'     => $seatId,
        ])->json('data.id');

        $this->patchJson("/api/bookings/{$bookingId}/cancel")
             ->assertStatus(200)
             ->assertJsonPath('data.status', BookingStatus::Cancelled->value);

      
        $this->patchJson("/api/bookings/{$bookingId}/cancel")
             ->assertStatus(200)
             ->assertJsonPath('data.status', BookingStatus::Cancelled->value);

        
        $this->assertSame(1, Booking::where('id', $bookingId)->count());
    }

    // -------------------------------------------------------------------------
    // Concurrency / locking strategy confirmation
    // -------------------------------------------------------------------------

    public function test_booking_service_uses_lock_for_update_on_seat(): void
    {
        $source = file_get_contents(app_path('Services/BookingService.php'));

       
        $lockPos  = strpos($source, 'lockForUpdate');
        $checkPos = strpos($source, 'alreadyBooked');

        $this->assertNotFalse($lockPos,  'lockForUpdate() must be present in BookingService');
        $this->assertNotFalse($checkPos, '$alreadyBooked check must be present in BookingService');

        
        $this->assertLessThan(
            $checkPos,
            $lockPos,
            'lockForUpdate() must be called BEFORE the $alreadyBooked existence check'
        );

        
        $this->assertStringContainsString(
            'DB::transaction',
            $source,
            'book() must wrap the operation in DB::transaction()'
        );
    }
}
