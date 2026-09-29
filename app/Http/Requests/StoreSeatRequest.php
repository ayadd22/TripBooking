<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {

        $busId = $this->route('bus')?->id;

        return [
            'seat_number' => [
                'required',
                'string',
                'max:10',
               
                \Illuminate\Validation\Rule::unique('seats', 'seat_number')
                    ->where('bus_id', $busId),
            ],
        ];
    }
}
