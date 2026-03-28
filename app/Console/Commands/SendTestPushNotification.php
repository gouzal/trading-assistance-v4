<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use Illuminate\Console\Command;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendTestPushNotification extends Command
{
    protected $signature   = 'notifications:test-push';
    protected $description = 'Send a test Strong Buy push notification to all subscriptions';

    public function handle(): int
    {
        $subscriptions = PushSubscription::all();

        if ($subscriptions->isEmpty()) {
            $this->warn('No push subscriptions found. Open the app on your phone first.');
            return self::SUCCESS;
        }

        $days    = rand(5, 30);
        $symbols = ['AAPL', 'NVDA', 'GOOGL', 'MSFT', 'TSLA', 'META', 'AMZN'];
        $symbol  = $symbols[array_rand($symbols)];

        $payload = json_encode([
            'title'   => "Strong Buy — {$symbol} [TEST]",
            'body'    => "Test notification · Earnings in {$days} days",
            'icon'    => '/icons/icon-192.png',
            'badge'   => '/icons/icon-192.png',
            'symbol'  => $symbol,
            'days'    => $days,
            'actions' => [
                ['action' => 'buy',     'title' => 'Buy'],
                ['action' => 'dismiss', 'title' => 'Dismiss'],
            ],
        ]);

        $auth = [
            'VAPID' => [
                'subject'    => config('services.vapid.subject'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth, [], 30, ['verify' => 'C:/Users/Larbi/cacert.pem']);

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

        $sent = 0;
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
            } else {
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
                }
                $this->warn('Failed: ' . $report->getReason());
            }
        }

        $this->info("Test notification [{$symbol} / {$days}d] sent to {$sent} device(s).");

        return self::SUCCESS;
    }
}
