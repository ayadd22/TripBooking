<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusRequest;
use App\Models\Bus;
use Illuminate\Http\JsonResponse;

class BusController extends Controller
{
    
    public function store(StoreBusRequest $request): JsonResponse
    {
        $bus = Bus::create($request->validated());

        return response()->json([
            'success' => true,
            'data'    => $bus,
        ], JsonResponse::HTTP_CREATED);
    }

    
    public function show(Bus $bus): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $bus,
        ]);
    }
}
