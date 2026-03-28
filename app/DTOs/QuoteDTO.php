<?php

namespace App\DTOs;

class QuoteDTO
{
    public function __construct(
        public readonly string $symbol,
        public readonly float $currentPrice,
        public readonly float $dayOpen,
        public readonly float $dayHigh,
        public readonly float $dayLow,
        public readonly float $dayChange,
        public readonly float $dayChangePercent,
        public readonly int $volume,
    ) {}
}
