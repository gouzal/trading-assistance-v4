<?php

namespace App\Http\Controllers;

use App\DTOs\OrderDTO;
use App\Http\Requests\TradingOrderRequest;
use App\Models\NotificationResponse;
use App\Models\TradingOrder;
use App\Services\TradingService;

class TradingController extends Controller
{
    public function __construct(private TradingService $tradingService) {}

    public function account()
    {
        $account   = $this->tradingService->getAccount();
        $positions = $this->tradingService->getPositions();
        return response()->json(compact('account', 'positions'));
    }

    public function place(TradingOrderRequest $request)
    {
        $data = $request->validated();
        $order = new OrderDTO(
            symbol:     strtoupper($data['symbol']),
            orderType:  $data['order_type'],
            orderClass: $data['order_class'],
            quantity:   (int) $data['quantity'],
            limitPrice: isset($data['limit_price']) ? (float) $data['limit_price'] : null,
        );

        try {
            $record = $this->tradingService->placeOrder($request->user()->id, $order);
            return response()->json(['success' => true, 'order' => $record], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function history()
    {
        $userId = auth()->id() ?? 0;

        $query = TradingOrder::where('user_id', $userId)
            ->orderByDesc('created_at');

        if (request('status')) {
            $query->where('status', request('status'));
        }

        $orders = $query->paginate(20)->withQueryString();

        $notificationResponses = NotificationResponse::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return view('orders.history', compact('orders', 'notificationResponses'));
    }
}
