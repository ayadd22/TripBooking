<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

   
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'trip_id'     => ['required', 'integer', 'exists:trips,id'],
            'seat_id'     => ['required', 'integer', 'exists:seats,id'],
        ];
    }
}
