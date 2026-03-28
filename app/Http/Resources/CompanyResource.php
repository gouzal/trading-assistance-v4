<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'symbol'      => $this->symbol,
            'name'        => $this->name,
            'logo_url'    => $this->logo_url,
            'is_favorite' => $this->is_favorite,
        ];
    }
}
