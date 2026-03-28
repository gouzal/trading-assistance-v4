<?php

namespace App\DTOs;

class PositionDTO
{
    public function __construct(
        public readonly string $symbol,
        public readonly int $quantity,
        public readonly float $avgEntryPrice,
        public readonly float $currentPrice,
        public readonly float $marketValue,
        public readonly float $unrealizedPl,
        public readonly float $unrealizedPlPercent,
    ) {}
}
