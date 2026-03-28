<?php

namespace App\Console\Commands;

use App\Models\Earning;
use App\Models\PushSubscription;
use App\Models\Sentiment;
use Illuminate\Console\Command;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendStrongBuyNotifications extends Command
{
    protected $signature   = 'notifications:send-strong-buy';
    protected $description = 'Send daily push notifications for Strong Buy companies with earnings in the next 30 days';

    public function handle(): int
    {
        $strongBuySymbols = Sentiment::where('analyst_rating', 'Strong Buy')
            ->pluck('symbol')
            ->toArray();

        if (empty($strongBuySymbols)) {
            $this->info('No Strong Buy companies found.');
            return self::SUCCESS;
        }

        // One row per symbol, ordered by soonest earnings
        $companies = Earning::whereIn('symbol', $strongBuySymbols)
            ->whereBetween('announcement_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->orderBy('announcement_date')
            ->with('company')
            ->get()
            ->unique('symbol');

        if ($companies->isEmpty()) {
            $this->info('No Strong Buy companies with earnings in the next 30 days.');
            return self::SUCCESS;
        }

        $subscriptions = PushSubscription::all();

        if ($subscriptions->isEmpty()) {
            $this->info('No push subscriptions registered.');
            return self::SUCCESS;
        }

        $auth = [
            'VAPID' => [
                'subject'    => config('services.vapid.subject'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth, [], 30, ['verify' => 'C:/Users/Larbi/cacert.pem']);
        $sent     = 0;
        $failed   = 0;

        // Send ONE notification per company so each has its own Buy / Dismiss buttons
        foreach ($companies as $earning) {
            $daysToEarnings = (int) now()->diffInDays($earning->announcement_date, false);
            // diffInDays with false gives negative for past — guard just in case
            $daysToEarnings = max(0, $daysToEarnings);

            $companyName = $earning->company?->name ?? $earning->symbol;

            $payload = json_encode([
                'title'   => "Strong Buy — {$earning->symbol}",
                'body'    => "{$companyName} · Earnings in {$daysToEarnings} day" . ($daysToEarnings === 1 ? '' : 's'),
                'icon'    => '/icons/icon-192.png',
                'badge'   => '/icons/icon-192.png',
                'symbol'  => $earning->symbol,
                'days'    => $daysToEarnings,
                'actions' => [
                    ['action' => 'buy',     'title' => 'Buy'],
                    ['action' => 'dismiss', 'title' => 'Dismiss'],
                ],
            ]);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint'        => $sub->endpoint,
                        'contentEncoding' => 'aesgcm',
                        'keys'            => [
                            'p256dh' => $sub->p256dh_key,
                            'auth'   => $sub->auth_token,
                        ],
                    ]),
                    $payload
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;
                } else {
                    $failed++;
                    if ($report->isSubscriptionExpired()) {
                        PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
                    }
                    $this->warn("Failed [{$earning->symbol}]: " . $report->getReason());
                }
            }
        }

        $this->info("Notifications sent: {$sent}, failed: {$failed}");

        return self::SUCCESS;
    }
}
