<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'symbol'            => $this->symbol,
            'announcement_date' => $this->announcement_date->toDateString(),
            'announcement_time' => $this->announcement_time,
            'estimated_eps'     => $this->estimated_eps,
            'actual_eps'        => $this->actual_eps,
            'estimated_revenue' => $this->estimated_revenue,
            'actual_revenue'    => $this->actual_revenue,
        ];
    }
}
