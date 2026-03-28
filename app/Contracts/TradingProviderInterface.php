<?php

namespace App\Contracts;

use App\DTOs\OrderDTO;
use App\DTOs\AccountDTO;
use App\DTOs\PositionDTO;

interface TradingProviderInterface
{
    public function getAccount(): AccountDTO;
    public function getPositions(): array; // PositionDTO[]
    public function placeOrder(OrderDTO $order): array;
    public function cancelOrder(string $orderId): bool;
    public function getOrderStatus(string $orderId): array;
}
