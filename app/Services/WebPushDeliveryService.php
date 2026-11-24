<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;

class WebPushDeliveryService
{
    public function send(PushSubscription $pushSubscription, array $payload): bool
    {
        $publicKey = config('webpush.public_key');
        $privateKey = config('webpush.private_key');

        if (! $publicKey || ! $privateKey) {
            throw new RuntimeException('Web Push VAPID keys are not configured.');
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.subject'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ], [
            'TTL' => 60,
            'urgency' => 'high',
        ]);

        $subscription = Subscription::create([
            'endpoint' => $pushSubscription->endpoint,
            'publicKey' => $pushSubscription->public_key,
            'authToken' => $pushSubscription->auth_token,
            'contentEncoding' => $pushSubscription->content_encoding,
        ]);

        $report = $webPush->sendOneNotification(
            $subscription,
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        if ($report->isSuccess()) {
            return true;
        }

        if ($report->isSubscriptionExpired()) {
            $pushSubscription->delete();
        }

        Log::warning('Web Push reminder delivery failed.', [
            'subscription_id' => $pushSubscription->id,
            'status_code' => $report->getResponse()?->getStatusCode(),
        ]);

        return false;
    }
}
