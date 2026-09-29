<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
  
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return response()->json([
            'success' => true,
            'data'    => $customer,
        ], JsonResponse::HTTP_CREATED);
    }

 
    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $customer,
        ]);
    }
}
