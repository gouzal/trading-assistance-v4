<?php

namespace App\DTOs;

class OrderDTO
{
    public function __construct(
        public readonly string $symbol,
        public readonly string $orderType,   // buy | sell
        public readonly string $orderClass,  // market | limit
        public readonly int $quantity,
        public readonly ?float $limitPrice = null,
    ) {}
}
