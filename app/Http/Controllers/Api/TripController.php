<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTripRequest;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;

class TripController extends Controller
{
   
    public function store(StoreTripRequest $request): JsonResponse
    {
        $trip = Trip::create($request->validated());

        return response()->json([
            'success' => true,
            'data'    => $trip,
        ], JsonResponse::HTTP_CREATED);
    }

    
    public function show(Trip $trip): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $trip,
        ]);
    }
}
