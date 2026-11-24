<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\PushSubscription;
use App\Models\ReminderDispatch;
use App\Services\WebPushDeliveryService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchReminders extends Command
{
    protected $signature = 'reminders:dispatch';

    protected $description = 'Envoie les rappels de pointage planifiés aux appareils inscrits.';

    public function handle(WebPushDeliveryService $deliveryService): int
    {
        $utcNow = CarbonImmutable::now('UTC');
        $hasFailures = false;

        foreach (Company::query()->where('reminder_enabled', true)->cursor() as $company) {
            try {
                $companyNow = $utcNow->setTimezone($company->timezone ?: config('app.timezone'));
            } catch (Throwable $exception) {
                $hasFailures = true;
                Log::error('Invalid timezone for company reminders.', [
                    'company_id' => $company->id,
                    'exception_class' => $exception::class,
                ]);
                $this->error("Fuseau horaire invalide pour l’entreprise {$company->id}.");

                continue;
            }

            foreach ($this->remindersFor($company) as $reminder) {
                if ($reminder['time'] !== $companyNow->format('H:i')) {
                    continue;
                }

                $hasFailures = $this->dispatchReminder($company, $companyNow, $reminder, $deliveryService)
                    || $hasFailures;
            }
        }

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }

    private function remindersFor(Company $company): array
    {
        $reminders = $company->reminders;
        if (! is_array($reminders) || $reminders === []) {
            $reminders = [[
                'time' => $company->reminder_time ?: '07:00',
                'message' => $company->reminder_message ?: 'N’oubliez pas de badger votre arrivée !',
            ]];
        }

        return collect($reminders)
            ->filter(fn ($reminder) => is_array($reminder)
                && isset($reminder['time'])
                && is_string($reminder['time'])
                && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $reminder['time']))
            ->map(fn ($reminder) => [
                'time' => $reminder['time'],
                'message' => is_string($reminder['message'] ?? null)
                    ? trim($reminder['message'])
                    : 'Rappel de pointage ChronoStaff',
            ])
            ->values()
            ->all();
    }

    private function dispatchReminder(
        Company $company,
        CarbonImmutable $companyNow,
        array $reminder,
        WebPushDeliveryService $deliveryService
    ): bool {
        $message = $reminder['message'] !== ''
            ? $reminder['message']
            : 'Rappel de pointage ChronoStaff';
        $messageHash = hash('sha256', $message);

        $dispatch = ReminderDispatch::firstOrCreate(
            [
                'company_id' => $company->id,
                'reminder_date' => $companyNow->toDateString(),
                'reminder_time' => $reminder['time'],
                'message_hash' => $messageHash,
            ],
            [
                'message' => $message,
                'status' => 'pending',
            ]
        );

        if (! $dispatch->wasRecentlyCreated) {
            return false;
        }

        $subscriptions = PushSubscription::query()
            ->whereHas('user', fn ($query) => $query
                ->where('company_id', $company->id)
                ->where('is_active', true))
            ->get();

        $sentCount = 0;
        $failedCount = 0;
        $lastError = null;
        $payload = [
            'title' => 'ChronoStaff — Rappel de pointage',
            'body' => $message,
            'tag' => 'chronostaff-reminder-'.$company->id.'-'.$reminder['time'].'-'.substr($messageHash, 0, 12),
            'url' => './',
        ];

        foreach ($subscriptions as $subscription) {
            try {
                if ($deliveryService->send($subscription, $payload)) {
                    $sentCount++;
                } else {
                    $failedCount++;
                    $lastError = 'Le service Push n’a pas accepté une ou plusieurs notifications.';
                }
            } catch (Throwable $exception) {
                $failedCount++;
                $lastError = 'Une erreur est survenue pendant l’envoi Push.';
                Log::error('Unable to send a scheduled ChronoStaff reminder.', [
                    'company_id' => $company->id,
                    'subscription_id' => $subscription->id,
                    'exception_class' => $exception::class,
                ]);
            }
        }

        $dispatch->update([
            'status' => $subscriptions->isEmpty()
                ? 'no_subscriptions'
                : ($failedCount === 0 ? 'sent' : ($sentCount > 0 ? 'partial' : 'failed')),
            'recipient_count' => $subscriptions->count(),
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'last_error' => $lastError,
            'sent_at' => $sentCount > 0 ? now() : null,
        ]);

        return $failedCount > 0;
    }
}
