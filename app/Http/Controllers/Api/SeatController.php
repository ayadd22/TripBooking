<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeatRequest;
use App\Models\Bus;
use Illuminate\Http\JsonResponse;

class SeatController extends Controller
{
   
    public function store(StoreSeatRequest $request, Bus $bus): JsonResponse
    {
       
        $seat = $bus->seats()->create([
            'seat_number' => $request->validated('seat_number'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $seat,
        ], JsonResponse::HTTP_CREATED);
    }

  
    public function index(Bus $bus): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $bus->seats,
        ]);
    }
}
