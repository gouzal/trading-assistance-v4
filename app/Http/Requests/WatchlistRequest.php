<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WatchlistRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'symbol' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z.]+$/'],
        ];
    }
}
