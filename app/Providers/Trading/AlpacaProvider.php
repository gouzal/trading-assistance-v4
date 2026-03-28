<?php

namespace App\Providers\Trading;

use App\Contracts\TradingProviderInterface;
use App\DTOs\OrderDTO;
use App\DTOs\AccountDTO;
use App\DTOs\PositionDTO;
use App\Models\ApiLog;
use Illuminate\Support\Facades\Http;

class AlpacaProvider implements TradingProviderInterface
{
    private string $baseUrl;
    private string $apiKey;
    private string $secret;

    public function __construct()
    {
        $paper = config('trading.paper_mode', true);
        $this->baseUrl = $paper ? config('services.alpaca.paper_url') : config('services.alpaca.live_url');
        $this->apiKey  = config('services.alpaca.key');
        $this->secret  = config('services.alpaca.secret');
    }

    private function client()
    {
        return Http::withHeaders([
            'APCA-API-KEY-ID'     => $this->apiKey,
            'APCA-API-SECRET-KEY' => $this->secret,
        ])->baseUrl($this->baseUrl)->timeout(10);
    }

    public function getAccount(): AccountDTO
    {
        $start = microtime(true);
        $response = $this->client()->get('/v2/account');
        $ms = (int) ((microtime(true) - $start) * 1000);
        ApiLog::record('alpaca', '/v2/account', null, $response->successful() ? 'success' : 'failed', $ms);

        $data = $response->json();
        return new AccountDTO(
            accountId: $data['id'] ?? '',
            cash: (float) ($data['cash'] ?? 0),
            portfolioValue: (float) ($data['portfolio_value'] ?? 0),
            buyingPower: (float) ($data['buying_power'] ?? 0),
            isPaperAccount: str_contains($this->baseUrl, 'paper'),
        );
    }

    public function getPositions(): array
    {
        $response = $this->client()->get('/v2/positions');
        $positions = [];
        $data = $response->json();
        if (!$response->successful() || !is_array($data) || array_is_list($data) === false) {
            return $positions;
        }
        foreach ($data as $p) {
            $positions[] = new PositionDTO(
                symbol: $p['symbol'],
                quantity: (int) $p['qty'],
                avgEntryPrice: (float) $p['avg_entry_price'],
                currentPrice: (float) $p['current_price'],
                marketValue: (float) $p['market_value'],
                unrealizedPl: (float) $p['unrealized_pl'],
                unrealizedPlPercent: (float) $p['unrealized_plpc'],
            );
        }
        return $positions;
    }

    public function placeOrder(OrderDTO $order): array
    {
        $start = microtime(true);
        $payload = [
            'symbol'        => $order->symbol,
            'qty'           => $order->quantity,
            'side'          => $order->orderType,
            'type'          => $order->orderClass,
            'time_in_force' => 'day',
        ];
        if ($order->limitPrice !== null) {
            $payload['limit_price'] = $order->limitPrice;
        }

        $response = $this->client()->post('/v2/orders', $payload);
        $ms = (int) ((microtime(true) - $start) * 1000);
        $status = $response->successful() ? 'success' : 'failed';
        ApiLog::record('alpaca', '/v2/orders', $order->symbol, $status, $ms, $response->successful() ? null : $response->body());

        return $response->json() ?? [];
    }

    public function cancelOrder(string $orderId): bool
    {
        $response = $this->client()->delete("/v2/orders/{$orderId}");
        return $response->successful();
    }

    public function getOrderStatus(string $orderId): array
    {
        return $this->client()->get("/v2/orders/{$orderId}")->json() ?? [];
    }
}
