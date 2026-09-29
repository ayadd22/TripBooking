<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BusController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SeatController;
use App\Http\Controllers\Api\TripController;
use Illuminate\Support\Facades\Route;



// ── Customers ─────────────────────────────────────────────────────────────────
Route::post('/customers',           [CustomerController::class, 'store'])->name('customers.store');
Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

// ── Buses ─────────────────────────────────────────────────────────────────────
Route::post('/buses',       [BusController::class, 'store'])->name('buses.store');
Route::get('/buses/{bus}',  [BusController::class, 'show'])->name('buses.show');

// ── Seats (nested under bus) ──────────────────────────────────────────────────
Route::post('/buses/{bus}/seats', [SeatController::class, 'store'])->name('buses.seats.store');
Route::get('/buses/{bus}/seats',  [SeatController::class, 'index'])->name('buses.seats.index');

// ── Trips ─────────────────────────────────────────────────────────────────────
Route::post('/trips',        [TripController::class, 'store'])->name('trips.store');
Route::get('/trips/{trip}',  [TripController::class, 'show'])->name('trips.show');

// ── Bookings ──────────────────────────────────────────────────────────────────
Route::get('/trips/{trip}/available-seats',    [BookingController::class, 'availableSeats'])->name('trips.available-seats');
Route::post('/bookings',                       [BookingController::class, 'store'])->name('bookings.store');
Route::patch('/bookings/{booking}/cancel',     [BookingController::class, 'cancel'])->name('bookings.cancel');
