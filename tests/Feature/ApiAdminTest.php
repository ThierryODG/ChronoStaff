<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Corp',
            'time_rounding' => '15min',
            'break_threshold_hours' => 6.0,
            'break_deduction_minutes' => 30,
        ]);

        $this->admin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Patron Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->employee = User::create([
            'company_id' => $this->company->id,
            'name' => 'Marc Salarié',
            'email' => 'marc@test.local',
            'password' => Hash::make('emp123'),
            'role' => 'employee',
            'is_active' => true,
        ]);
    }

    public function test_who_is_here_dashboard_shows_clocked_in_employees(): void
    {
        // Clock employee IN
        TimeLog::create([
            'user_id' => $this->employee->id,
            'type' => 'in',
            'punched_at' => now()->subHours(2),
            'is_on_site' => true,
        ]);

        $token = $this->admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/dashboard/who-is-here');

        $response->assertStatus(200)
            ->assertJson([
                'summary' => [
                    'currently_present' => 1,
                    'currently_absent' => 0,
                ],
                'present_employees' => [
                    [
                        'employee_id' => $this->employee->id,
                        'name' => 'Marc Salarié',
                        'location_label' => 'Sur site',
                    ],
                ],
            ]);
    }

    public function test_manual_adjustment_requires_mandatory_justification_note(): void
    {
        $token = $this->admin->createToken('admin-token')->plainTextToken;

        // Fails without admin_note
        $failResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/manual-adjustments', [
                'user_id' => $this->employee->id,
                'type' => 'in',
                'punched_at' => now()->toIso8601String(),
            ]);

        $failResponse->assertStatus(422)
            ->assertJsonValidationErrors(['admin_note']);

        // Succeeds with note
        $successResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/manual-adjustments', [
                'user_id' => $this->employee->id,
                'type' => 'in',
                'punched_at' => now()->toIso8601String(),
                'admin_note' => 'Oubli de badger ce matin suite à une réunion urgente avec le client.',
            ]);

        $successResponse->assertStatus(201)
            ->assertJson([
                'message' => 'Ajustement manuel enregistré avec succès.',
                'time_log' => [
                    'is_manual' => true,
                    'admin_note' => 'Oubli de badger ce matin suite à une réunion urgente avec le client.',
                ],
            ]);
    }

    public function test_report_calculates_worked_hours_with_break_deduction(): void
    {
        // Shift of 8 hours (08:00 to 16:00). Rule: -30 min pause if > 6h -> Net 7.5 hours (7h 30m)
        TimeLog::create([
            'user_id' => $this->employee->id,
            'type' => 'in',
            'punched_at' => Carbon::parse('2026-11-23 08:00:00'),
        ]);

        TimeLog::create([
            'user_id' => $this->employee->id,
            'type' => 'out',
            'punched_at' => Carbon::parse('2026-11-23 16:00:00'),
        ]);

        $token = $this->admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/reports?start_date=2026-11-23&end_date=2026-11-23');

        $response->assertStatus(200)
            ->assertJson([
                'employees' => [
                    [
                        'name' => 'Marc Salarié',
                        'total_shifts' => 1,
                        'total_raw_hours' => 8.0,
                        'total_break_deducted_minutes' => 30,
                        'total_adjusted_hours' => 7.5,
                        'total_formatted' => '7h 30m',
                    ],
                ],
            ]);
    }
}

