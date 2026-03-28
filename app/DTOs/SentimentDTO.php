<?php

namespace App\DTOs;

class SentimentDTO
{
    public function __construct(
        public readonly string $symbol,
        public readonly ?float $sentimentScore,
        public readonly ?string $sentimentLabel,
        public readonly ?string $analystRating,
        public readonly ?int $buyCount,
        public readonly ?int $holdCount,
        public readonly ?int $sellCount,
        public readonly array $newsData = [],
        public readonly bool $revisedExpectations = false,
        public readonly ?string $revisionDirection = null,
    ) {}
}
