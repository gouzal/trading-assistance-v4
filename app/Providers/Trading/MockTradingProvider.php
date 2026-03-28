<?php

namespace App\Providers\Trading;

use App\Contracts\TradingProviderInterface;
use App\DTOs\OrderDTO;
use App\DTOs\AccountDTO;
use App\DTOs\PositionDTO;

class MockTradingProvider implements TradingProviderInterface
{
    public function getAccount(): AccountDTO
    {
        return new AccountDTO('mock-account', 10000.00, 25000.00, 10000.00, true);
    }

    public function getPositions(): array
    {
        return [
            new PositionDTO('AAPL', 10, 145.00, 150.00, 1500.00, 50.00, 0.034),
        ];
    }

    public function placeOrder(OrderDTO $order): array
    {
        return [
            'id'     => 'mock-order-' . uniqid(),
            'symbol' => $order->symbol,
            'qty'    => $order->quantity,
            'side'   => $order->orderType,
            'type'   => $order->orderClass,
            'status' => 'accepted',
        ];
    }

    public function cancelOrder(string $orderId): bool { return true; }
    public function getOrderStatus(string $orderId): array { return ['status' => 'filled']; }
}
