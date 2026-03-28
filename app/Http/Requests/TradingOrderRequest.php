<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TradingOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'symbol'      => ['required', 'string', 'max:10'],
            'order_type'  => ['required', 'in:buy,sell'],
            'order_class' => ['required', 'in:market,limit'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'limit_price' => ['nullable', 'numeric', 'min:0.01', 'required_if:order_class,limit'],
        ];
    }
}
