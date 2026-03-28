<?php

namespace App\Services;

use App\Contracts\TradingProviderInterface;
use App\DTOs\AccountDTO;
use App\DTOs\OrderDTO;
use App\Models\TradingOrder;
use Illuminate\Support\Facades\Cache;

class TradingService
{
    public function __construct(
        private TradingProviderInterface $provider
    ) {}

    public function getAccount(): AccountDTO
    {
        return Cache::remember('trading_assistant.account', 60, fn () => $this->provider->getAccount());
    }

    public function getPositions(): array
    {
        return Cache::remember('trading_assistant.positions', 60, fn () => $this->provider->getPositions());
    }

    public function placeOrder(int $userId, OrderDTO $order): TradingOrder
    {
        if (!config('trading.enable_trading')) {
            throw new \RuntimeException('Trading is disabled. Set ENABLE_TRADING=true in .env to enable.');
        }

        $dailyCount = TradingOrder::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->whereIn('status', ['submitted', 'filled', 'partial'])
            ->count();

        if ($dailyCount >= config('trading.daily_trade_limit', 5)) {
            throw new \RuntimeException('Daily trade limit reached.');
        }

        $record = TradingOrder::create([
            'user_id'      => $userId,
            'symbol'       => $order->symbol,
            'order_type'   => $order->orderType,
            'order_class'  => $order->orderClass,
            'quantity'     => $order->quantity,
            'limit_price'  => $order->limitPrice,
            'status'       => 'pending',
            'submitted_at' => now(),
        ]);

        try {
            $result = $this->provider->placeOrder($order);
            $record->update([
                'status'          => 'submitted',
                'alpaca_order_id' => $result['id'] ?? null,
            ]);
        } catch (\Exception $e) {
            $record->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }

        // Invalidate account + positions cache
        Cache::forget('trading_assistant.account');
        Cache::forget('trading_assistant.positions');

        return $record->fresh();
    }
}
