<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bus_number' => ['required', 'string', 'max:50', 'unique:buses,bus_number'],
            'capacity'   => ['required', 'integer', 'min:1'],
        ];
    }
}
