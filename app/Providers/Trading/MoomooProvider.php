<?php

namespace App\Providers\Trading;

use App\Contracts\TradingProviderInterface;
use App\DTOs\OrderDTO;
use App\DTOs\AccountDTO;
use App\DTOs\PositionDTO;

class MoomooProvider implements TradingProviderInterface
{
    public function getAccount(): AccountDTO
    {
        throw new \RuntimeException('Moomoo provider not yet implemented.');
    }

    public function getPositions(): array { return []; }
    public function placeOrder(OrderDTO $order): array { return []; }
    public function cancelOrder(string $orderId): bool { return false; }
    public function getOrderStatus(string $orderId): array { return []; }
}
