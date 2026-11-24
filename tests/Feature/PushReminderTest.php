<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PushSubscription;
use App\Models\ReminderDispatch;
use App\Models\User;
use App\Services\WebPushDeliveryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PushReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_and_remove_a_push_subscription(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Alice Martin',
            'email' => 'alice-push@test.local',
            'password' => 'password',
            'role' => 'employee',
            'is_active' => true,
        ]);
        $token = $user->createToken('test-device')->plainTextToken;
        $endpoint = 'https://push.example.test/subscription/opaque-token';

        $this->withToken($token)->postJson('/api/notifications/subscriptions', [
            'endpoint' => $endpoint,
            'keys' => $this->validPushKeys(),
        ])->assertCreated();

        $subscription = PushSubscription::firstOrFail();
        $this->assertSame($endpoint, $subscription->endpoint);
        $this->assertSame($this->validPushKeys()['auth'], $subscription->auth_token);
        $this->assertSame($user->id, $subscription->user_id);

        $this->withToken($token)->deleteJson('/api/notifications/subscriptions', [
            'endpoint' => $endpoint,
        ])->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_push_subscription_rejects_non_https_endpoints(): void
    {
        $user = User::create([
            'name' => 'Alice Martin',
            'email' => 'alice-http-push@test.local',
            'password' => 'password',
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->withToken($user->createToken('test-device')->plainTextToken)
            ->postJson('/api/notifications/subscriptions', [
                'endpoint' => 'http://push.example.test/subscription/token',
                'keys' => $this->validPushKeys(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    }

    public function test_backend_sends_a_company_reminder_at_its_local_time_only_once_per_day(): void
    {
        $company = Company::create([
            'name' => 'Acme',
            'timezone' => 'Europe/Paris',
            'reminder_enabled' => true,
            'reminders' => [
                ['time' => '07:00', 'message' => 'Votre journée commence : pensez à badger.'],
            ],
        ]);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Alice Martin',
            'email' => 'alice-reminder@test.local',
            'password' => 'password',
            'role' => 'employee',
            'is_active' => true,
        ]);
        $endpoint = 'https://push.example.test/subscription/reminder-token';
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint_hash' => hash('sha256', $endpoint),
            'endpoint' => $endpoint,
            'public_key' => $this->validPushKeys()['p256dh'],
            'auth_token' => $this->validPushKeys()['auth'],
            'content_encoding' => 'aes128gcm',
        ]);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-24 06:00:00', 'UTC'));

        $sender = Mockery::mock(WebPushDeliveryService::class);
        $sender->shouldReceive('send')
            ->once()
            ->withArgs(fn (PushSubscription $subscription, array $payload) => $subscription->user_id === $user->id
                && $payload['body'] === 'Votre journée commence : pensez à badger.'
                && str_starts_with($payload['tag'], 'chronostaff-reminder-'.$company->id.'-07:00-')
            )
            ->andReturnTrue();
        $this->app->instance(WebPushDeliveryService::class, $sender);

        $this->artisan('reminders:dispatch')->assertSuccessful();
        $this->artisan('reminders:dispatch')->assertSuccessful();

        $this->assertDatabaseHas('reminder_dispatches', [
            'company_id' => $company->id,
            'reminder_date' => '2026-11-24',
            'reminder_time' => '07:00',
            'status' => 'sent',
            'recipient_count' => 1,
            'sent_count' => 1,
        ]);
        $this->assertSame(1, ReminderDispatch::count());
    }

    public function test_backend_does_not_send_a_reminder_before_its_company_local_time(): void
    {
        $company = Company::create([
            'name' => 'Acme',
            'timezone' => 'Europe/Paris',
            'reminder_enabled' => true,
            'reminders' => [
                ['time' => '07:00', 'message' => 'Pensez à badger.'],
            ],
        ]);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Alice Martin',
            'email' => 'alice-early-reminder@test.local',
            'password' => 'password',
            'role' => 'employee',
            'is_active' => true,
        ]);
        $endpoint = 'https://push.example.test/subscription/early-reminder';
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint_hash' => hash('sha256', $endpoint),
            'endpoint' => $endpoint,
            'public_key' => $this->validPushKeys()['p256dh'],
            'auth_token' => $this->validPushKeys()['auth'],
            'content_encoding' => 'aes128gcm',
        ]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-24 05:59:00', 'UTC'));

        $sender = Mockery::mock(WebPushDeliveryService::class);
        $sender->shouldNotReceive('send');
        $this->app->instance(WebPushDeliveryService::class, $sender);

        $this->artisan('reminders:dispatch')->assertSuccessful();

        $this->assertSame(0, ReminderDispatch::count());
    }

    private function validPushKeys(): array
    {
        return [
            'p256dh' => rtrim(strtr(base64_encode(str_repeat('p', 65)), '+/', '-_'), '='),
            'auth' => rtrim(strtr(base64_encode(str_repeat('a', 16)), '+/', '-_'), '='),
        ];
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }
}
