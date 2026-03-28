<?php

namespace App\Http\Controllers;

use App\Models\NotificationResponse;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'              => 'required|url',
            'keys.p256dh'           => 'required|string',
            'keys.auth'             => 'required|string',
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $request->input('endpoint')],
            [
                'user_id'    => $request->user()->id,
                'p256dh_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
            ]
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|string']);

        PushSubscription::where('endpoint', $request->input('endpoint'))
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['status' => 'unsubscribed']);
    }

    public function vapidKey(): JsonResponse
    {
        return response()->json(['public_key' => config('services.vapid.public_key')]);
    }

    public function recordResponse(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string|max:20',
            'action' => 'required|in:buy,dismiss',
            'days'   => 'nullable|integer|min:0',
        ]);

        NotificationResponse::create([
            'user_id'         => $request->user()->id,
            'symbol'          => strtoupper($request->input('symbol')),
            'action'          => $request->input('action'),
            'days_to_earnings'=> $request->input('action') === 'buy' ? $request->input('days') : null,
        ]);

        return response()->json(['status' => 'recorded']);
    }
}
