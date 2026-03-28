<?php

namespace App\DTOs;

class CompanyProfileDTO
{
    public function __construct(
        public readonly string $symbol,
        public readonly string $name,
        public readonly ?string $sector,
        public readonly ?string $industry,
        public readonly ?string $country,
        public readonly ?string $logoUrl,
    ) {}
}
