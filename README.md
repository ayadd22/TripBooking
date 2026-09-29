# Al-Rahala Trip Booking API

A RESTful API for managing bus trips, seats, customers, and trip bookings.

The system is built with **Laravel 12**, **PHP**, and **MySQL**. It focuses on clean architecture, business-rule enforcement, API validation, and safe seat booking under concurrent requests.

---

## About the Project

**Al-Rahala Trip Booking API** is a backend system for managing bus trips and seat reservations.

The system allows:

* Creating and viewing customers
* Creating and viewing buses
* Adding seats to buses
* Creating and viewing trips
* Checking available seats for a trip
* Creating bookings
* Cancelling bookings
* Preventing the same seat from being actively booked twice for the same trip
* Preventing a seat from being used for a trip if it belongs to another bus

The project is implemented as an **API-only Laravel application**. It does not use Blade pages or a frontend interface.

---

## Technologies

* **PHP**
* **Laravel 12**
* **MySQL**
* **Laravel Eloquent ORM**
* **Laravel Form Requests**
* **PHP Enums**
* **Laravel Service Container**
* **Dependency Injection**
* **Database Transactions**
* **Pest/PHPUnit Feature Tests**
* **RESTful API**

---

# System Entities

The system contains five main entities:

### Customer

Represents a customer who can make bookings.

Fields:

* `id`
* `name`
* `phone`

Relationship:

```text
Customer 1 ──────── * Booking
```

A customer can have many bookings.

---

### Bus

Represents a bus used for trips.

Fields:

* `id`
* `bus_number`
* `capacity`

Relationships:

```text
Bus 1 ──────── * Seat
Bus 1 ──────── * Trip
```

A bus can have many seats and can be assigned to many trips.

---

### Seat

Represents a physical seat belonging to a specific bus.

Fields:

* `id`
* `bus_id`
* `seat_number`

Relationship:

```text
Bus 1 ──────── * Seat
Seat 1 ──────── * Booking
```

A seat belongs to one bus and can appear in many bookings over time.

The same seat number is allowed on different buses.

For example:

```text
Bus 1 → Seat 1
Bus 2 → Seat 1
```

This is valid because seat numbers are unique **within the same bus**, not globally.

The database enforces this using:

```php
$table->unique(['bus_id', 'seat_number']);
```

---

### Trip

Represents a scheduled journey.

Fields:

* `id`
* `bus_id`
* `departure_city`
* `arrival_city`
* `departure_at`

Relationships:

```text
Bus 1 ──────── * Trip
Trip 1 ──────── * Booking
```

Each trip belongs to one bus and can have many bookings.

---

### Booking

Represents a customer's reservation for a specific seat on a specific trip.

Fields:

* `id`
* `customer_id`
* `trip_id`
* `seat_id`
* `status`

Relationships:

```text
Customer 1 ──────── * Booking
Trip     1 ──────── * Booking
Seat     1 ──────── * Booking
```

A booking belongs to one customer, one trip, and one seat.

---

# Entity Relationship Overview

```text
Customer
   │
   │ 1 : many
   ▼
Booking
   ▲
   │
   ├──────── Trip ──────── Bus
   │           │            │
   │           │            │
   │           │            └────── * Seats
   │           │
   │           └────── * Bookings
   │
   └──────── Seat
```

More specifically:

```text
Customer 1 ──────── * Booking
Trip     1 ──────── * Booking
Seat     1 ──────── * Booking

Bus      1 ──────── * Seat
Bus      1 ──────── * Trip
```

---

# Booking Status

The booking status is represented using a PHP Enum:

```php
enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
```

The system has three booking states:

| Status      | Meaning                           | Seat considered occupied? |
| ----------- | --------------------------------- | ------------------------- |
| `pending`   | Booking created but not confirmed | Yes                       |
| `confirmed` | Booking confirmed                 | Yes                       |
| `cancelled` | Booking cancelled                 | No                        |

## Active Bookings

A booking is considered **active** when its status is:

```text
pending
confirmed
```

A cancelled booking is not active.

The system centralizes these active statuses in the `Booking` model:

```php
protected array $activeStatuses = [
    BookingStatus::Pending->value,
    BookingStatus::Confirmed->value,
];
```

This means the system does not need to repeat:

```php
whereIn('status', ['pending', 'confirmed'])
```

every time it needs to find active bookings.

---

## HasActiveScope Trait

The `Booking` model uses:

```php
use HasActiveScope;
```

The trait provides the reusable `active()` query scope:

```php
public function scopeActive(Builder $query): Builder
{
    return $query->whereIn('status', $this->activeStatuses());
}
```

Therefore, instead of writing:

```php
Booking::whereIn('status', [
    BookingStatus::Pending->value,
    BookingStatus::Confirmed->value,
])
```

the application can simply use:

```php
Booking::active()
```

This is used when calculating availability and checking whether a seat is already booked.

### Why use `active()`?

Because both `pending` and `confirmed` bookings occupy a seat.

For example:

```text
Trip 1
Seat 5

Booking #1 → pending
```

Seat 5 is occupied.

If the booking becomes:

```text
Booking #1 → cancelled
```

Seat 5 becomes available again.

---

## Enum `isActive()` Removal

The `BookingStatus` Enum previously contained an `isActive()` method.

That method was removed because the project already determines active statuses through:

```php
$activeStatuses
```

and the reusable:

```php
Booking::active()
```

scope.

Keeping another `isActive()` method would duplicate the same business concept in two different places.

The Enum is therefore responsible for defining the valid booking statuses, while the `HasActiveScope` trait and `$activeStatuses` property handle querying active bookings.

---

# Business Rules

The system enforces the following rules.

## 1. A seat must belong to the trip's bus

A booking cannot use a seat belonging to another bus.

For example:

```text
Trip 1 → Bus 1
Seat 5 → Bus 2
```

This booking is rejected.

The system throws:

```text
SeatBusMismatchException
```

---

## 2. A seat cannot have two active bookings on the same trip

The system checks:

```php
Booking::active()
    ->where('trip_id', $tripId)
    ->where('seat_id', $seatId)
    ->exists();
```

If an active booking already exists, the new booking is rejected.

Cancelled bookings do not block the seat.

Example:

```text
Trip 1 + Seat 5 → confirmed
Trip 1 + Seat 5 → another booking
```

The second booking is rejected.

---

## 3. Available seats are calculated dynamically

The database does not store a `remaining_seats` column.

Instead:

```text
Available Seats
=
Total Seats on Bus
-
Active Bookings for Trip
```

This prevents duplicated and potentially inconsistent stock-like information.

---

# Booking Service

Business logic is placed inside:

```text
app/Services/BookingService.php
```

The service handles:

* Available seat calculation
* Seat validation
* Booking creation
* Booking cancellation
* Concurrency protection

The controller does not contain the booking business logic.

Instead:

```text
Controller
    ↓
BookingService
    ↓
Models / Database
```

This keeps controllers small and makes the business rules easier to maintain and test.

---

# Booking Service Interface

The service is defined through:

```text
app/Contracts/BookingServiceInterface.php
```

The interface defines the operations required by the booking system:

```php
public function getAvailableSeatsCount(Trip $trip): int;

public function book(
    int $customerId,
    int $tripId,
    int $seatId
): Booking;

public function cancel(Booking $booking): Booking;
```

The interface allows the controller to depend on an abstraction rather than directly depending on the concrete service implementation.

---

# Dependency Injection

`BookingController` receives the booking service through its constructor:

```php
public function __construct(
    private readonly BookingServiceInterface $bookingService,
) {}
```

The Laravel Service Container is configured in:

```text
app/Providers/AppServiceProvider.php
```

with:

```php
$this->app->bind(
    BookingServiceInterface::class,
    BookingService::class,
);
```

This tells Laravel:

```text
BookingServiceInterface
        ↓
BookingService
```

The constructor says:

> I need a `BookingServiceInterface`.

The `bind()` configuration tells Laravel:

> When you need that interface, provide `BookingService`.

This keeps dependency injection explicit and allows the implementation to be replaced later without changing the controller.

---

# Concurrency Protection

The booking operation uses a database transaction:

```php
DB::transaction(...)
```

and locks the selected seat:

```php
->lockForUpdate()
```

The purpose is to prevent two requests from booking the same seat at the same time.

The simplified flow is:

```text
Request A ──┐
            ├── Lock Seat
Request B ──┘       ↓
                Check booking
                    ↓
                Create booking
```

Only after the seat is safely locked does the system check for an existing active booking.

This protects the critical booking operation against concurrent requests.

---

# Exceptions

The system uses specific exceptions for booking-related business errors.

## SeatBusMismatchException

Used when the selected seat does not belong to the bus assigned to the trip.

```text
Seat does not belong to the trip's bus.
```

---

## SeatNotAvailableException

Used when the selected seat already has an active booking for the trip.

```text
Seat is already booked for this trip.
```

Both exceptions are converted into JSON API responses with HTTP status `422`.

---

# Form Request Validation

The project uses Laravel Form Requests for request validation.

Examples include:

```text
StoreCustomerRequest
StoreBusRequest
StoreSeatRequest
StoreTripRequest
StoreBookingRequest
```

This keeps validation separate from controllers.

For seats, uniqueness is scoped to the bus:

```php
Rule::unique('seats', 'seat_number')
    ->where('bus_id', $busId)
```

Therefore:

```text
Bus 1 → Seat 1   ✓
Bus 2 → Seat 1   ✓
Bus 1 → Seat 1   ✗
```

The database also enforces the same rule with a composite unique constraint.

---

# API Endpoints

## Customers

### Create Customer

```http
POST /api/customers
```

### Show Customer

```http
GET /api/customers/{customer}
```

---

## Buses

### Create Bus

```http
POST /api/buses
```

### Show Bus

```http
GET /api/buses/{bus}
```

---

## Seats

### Add Seat to Bus

```http
POST /api/buses/{bus}/seats
```

### List Bus Seats

```http
GET /api/buses/{bus}/seats
```

---

## Trips

### Create Trip

```http
POST /api/trips
```

### Show Trip

```http
GET /api/trips/{trip}
```

### Get Available Seats

```http
GET /api/trips/{trip}/available-seats
```

Example response:

```json
{
    "success": true,
    "data": {
        "trip_id": 1,
        "available_seats": 25
    }
}
```

---

## Bookings

### Create Booking

```http
POST /api/bookings
```

Example request:

```json
{
    "customer_id": 1,
    "trip_id": 1,
    "seat_id": 5
}
```

### Cancel Booking

```http
PATCH /api/bookings/{booking}/cancel
```

---

# API Response Format

Successful responses use a consistent structure:

```json
{
    "success": true,
    "data": {}
}
```

Errors use:

```json
{
    "success": false,
    "message": "..."
}
```

Validation errors additionally include:

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {}
}
```

---

# Error Handling

API exceptions are handled centrally in:

```text
bootstrap/app.php
```

The application provides JSON responses for:

* Model not found → `404`
* Route not found → `404`
* Validation errors → `422`
* Seat already booked → `422`
* Seat/bus mismatch → `422`
* Unexpected server errors → `500`

This keeps API error responses consistent across the application.

---

# Project Structure

Important project directories:

```text
app/
├── Contracts/
│   └── BookingServiceInterface.php
│
├── Enums/
│   └── BookingStatus.php
│
├── Exceptions/
│   ├── SeatBusMismatchException.php
│   └── SeatNotAvailableException.php
│
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   └── Requests/
│
├── Models/
│   ├── Booking.php
│   ├── Bus.php
│   ├── Customer.php
│   ├── Seat.php
│   └── Trip.php
│
├── Providers/
│   └── AppServiceProvider.php
│
├── Services/
│   └── BookingService.php
│
└── Traits/
    └── HasActiveScope.php

database/
└── migrations/

routes/
└── api.php

tests/
└── Feature/
    └── BookingScenarioTest.php
```

---

# Testing

The project includes feature tests covering the main booking scenarios.

The tests verify:

* Complete booking workflow
* Booking a seat from another bus
* Non-existent customer validation
* Non-existent trip validation
* Non-existent seat validation
* Non-existent trip when checking availability
* Non-existent booking cancellation
* Cancelling an already cancelled booking
* Transaction and `lockForUpdate()` usage

Run the test suite with:

```bash
php artisan test
```

or:

```bash
vendor\bin\phpunit
```

---

# Installation

## 1. Clone the project

```bash
git clone <repository-url>
cd TripBooking
```

## 2. Install dependencies

```bash
composer install
```

## 3. Create environment file

```bash
copy .env.example .env
```

## 4. Generate application key

```bash
php artisan key:generate
```

## 5. Configure the database

Update the database settings in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trip_booking
DB_USERNAME=root
DB_PASSWORD=
```

## 6. Run migrations

```bash
php artisan migrate
```

## 7. Run the application

```bash
php artisan serve
```

The API will be available through:

```text
http://127.0.0.1:8000
```

API endpoints are available under:

```text
/api
```

---

# Architecture Overview

The application follows a layered approach:

```text
API Request
     ↓
Form Request
     ↓
Controller
     ↓
Service Interface
     ↓
Booking Service
     ↓
Eloquent Models
     ↓
MySQL Database
```

### Controller

Responsible for:

* Receiving the request
* Calling the required service
* Returning the API response

### Form Request

Responsible for:

* Validating incoming data

### Service

Responsible for:

* Business rules
* Booking logic
* Seat availability
* Transactions
* Concurrency protection

### Model

Responsible for:

* Database representation
* Relationships
* Eloquent behavior

### Enum

Responsible for:

* Defining valid booking status values

### Trait

Responsible for:

* Reusing the `active()` booking query scope

---

# Design Principles Used

The project applies several backend development principles:

* Separation of concerns
* Dependency Injection
* Programming to an interface
* Service Layer
* Reusable query scopes
* Form Request validation
* Database transactions
* Database-level constraints
* Explicit business exceptions
* Automated feature testing

The goal is to keep business logic out of controllers and make the system easier to maintain, test, and extend.

---

# License

This project is developed as part of a Laravel backend training assignment.
