<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bus_id'         => ['required', 'integer', 'exists:buses,id'],
            'departure_city' => ['required', 'string', 'max:255'],
            'arrival_city'   => ['required', 'string', 'max:255'],
            'departure_at'   => ['required', 'date'],
        ];
    }
}
