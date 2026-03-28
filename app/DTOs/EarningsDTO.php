<?php

namespace App\DTOs;

class EarningsDTO
{
    public function __construct(
        public readonly string $symbol,
        public readonly string $announcementDate,
        public readonly ?string $announcementTime,
        public readonly ?float $estimatedRevenue,
        public readonly ?float $actualRevenue,
        public readonly ?float $estimatedEps,
        public readonly ?float $actualEps,
        public readonly array $rawData = [],
    ) {}
}
