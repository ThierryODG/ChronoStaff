<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiClockAndSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Siège ChronoStaff',
            'latitude' => 48.8698,
            'longitude' => 2.3314,
            'cooldown_enabled' => false, // Désactivé pour les tests de toggle rapide
        ]);

        $this->employee = User::create([
            'company_id' => $this->company->id,
            'name' => 'Claire Delacour',
            'email' => 'claire@test.local',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'pin_code' => '9988',
            'qr_token' => 'qr-token-claire',
            'is_active' => true,
        ]);
    }

    public function test_employee_can_punch_in_and_out_using_qr_code(): void
    {
        // 1. First punch -> IN
        $response1 = $this->postJson('/api/clock/punch', [
            'qr_token' => 'qr-token-claire',
            'latitude' => 48.8698, // Exactly on site
            'longitude' => 2.3314,
        ]);

        $response1->assertStatus(201)
            ->assertJson([
                'success' => true,
                'action' => 'in',
                'time_log' => [
                    'is_on_site' => true,
                ],
            ]);

        $this->assertDatabaseHas('time_logs', [
            'user_id' => $this->employee->id,
            'type' => 'in',
            'is_on_site' => true,
        ]);

        // 2. Second punch -> Automatically toggles to OUT
        $response2 = $this->postJson('/api/clock/punch', [
            'qr_token' => 'qr-token-claire',
            'latitude' => 48.9000, // Away from company
            'longitude' => 2.4000,
        ]);

        $response2->assertStatus(201)
            ->assertJson([
                'success' => true,
                'action' => 'out',
                'time_log' => [
                    'is_on_site' => false,
                ],
            ]);

        $this->assertDatabaseHas('time_logs', [
            'user_id' => $this->employee->id,
            'type' => 'out',
            'is_on_site' => false,
        ]);
    }

    public function test_employee_can_punch_using_pin_fallback(): void
    {
        $response = $this->postJson('/api/clock/punch', [
            'pin_code' => '9988',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'action' => 'in',
            ]);
    }

    public function test_offline_batch_synchronization_with_deduplication(): void
    {
        $token = $this->employee->createToken('test')->plainTextToken;
        $offlineId1 = (string) Str::uuid();
        $offlineId2 = (string) Str::uuid();

        // Simulate 2 offline punches (IN at 08:00, OUT at 12:00)
        $payload = [
            'punches' => [
                [
                    'offline_id' => $offlineId1,
                    'type' => 'in',
                    'punched_at' => '2026-11-23T08:00:00Z',
                    'latitude' => 48.8698,
                    'longitude' => 2.3314,
                ],
                [
                    'offline_id' => $offlineId2,
                    'type' => 'out',
                    'punched_at' => '2026-11-23T12:00:00Z',
                    'latitude' => 48.8698,
                    'longitude' => 2.3314,
                ],
            ],
        ];

        // 1. Initial sync
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/clock/sync', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total_received' => 2,
                    'synced_count' => 2,
                    'duplicates_count' => 0,
                ],
            ]);

        $this->assertDatabaseCount('time_logs', 2);

        // 2. Re-sending the same offline batch (simulating connection retry) -> Must deduplicate!
        $retryResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/clock/sync', $payload);

        $retryResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total_received' => 2,
                    'synced_count' => 0,
                    'duplicates_count' => 2,
                ],
            ]);

        // Still strictly 2 logs in database, zero duplicates
        $this->assertDatabaseCount('time_logs', 2);
    }

    public function test_cooldown_prevents_immediate_clock_out_when_enabled(): void
    {
        $this->company->update([
            'cooldown_enabled' => true,
            'min_punch_interval_minutes' => 60,
        ]);

        // 1. Arrivée (IN)
        $res1 = $this->postJson('/api/clock/punch', [
            'pin_code' => '9988',
        ]);
        $res1->assertStatus(201)->assertJson(['action' => 'in']);

        // 2. Tentative immédiate de départ (OUT) -> Doit être bloqué avec 422
        $res2 = $this->postJson('/api/clock/punch', [
            'pin_code' => '9988',
        ]);
        $res2->assertStatus(422)
            ->assertJson([
                'success' => false,
                'cooldown' => true,
            ]);
    }
}

