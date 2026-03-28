<?php

namespace App\DTOs;

class AccountDTO
{
    public function __construct(
        public readonly string $accountId,
        public readonly float $cash,
        public readonly float $portfolioValue,
        public readonly float $buyingPower,
        public readonly bool $isPaperAccount,
    ) {}
}
