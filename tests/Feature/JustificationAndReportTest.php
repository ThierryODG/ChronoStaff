<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Justification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JustificationAndReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'work_start_time' => '08:00',
            'work_end_time' => '17:00',
            'overtime_enabled' => true,
        ]);

        $department = Department::create([
            'name' => 'IT Support',
            'company_id' => $this->company->id,
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'company_id' => $this->company->id,
            'department_id' => $department->id,
        ]);

        $this->employee = User::create([
            'name' => 'Employee User',
            'email' => 'employee@test.com',
            'password' => bcrypt('password123'),
            'role' => 'employee',
            'company_id' => $this->company->id,
            'department_id' => $department->id,
            'pin_code' => '1234',
        ]);
    }

    public function test_employee_can_submit_justification(): void
    {
        $response = $this->actingAs($this->employee, 'sanctum')->postJson('/api/employee/justification', [
            'date' => '2026-11-23',
            'type' => 'retard',
            'reason' => 'Panne de transport ce matin.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('justifications', [
            'user_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'type' => 'retard',
            'reason' => 'Panne de transport ce matin.',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_update_justification_status(): void
    {
        $justification = Justification::create([
            'user_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'date' => '2026-11-23',
            'reason' => 'absence',
            'details' => 'Rendez-vous médical.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/adjustments/justifications/{$justification->id}/status", [
            'status' => 'approved',
            'admin_comment' => 'Certificat médical reçu.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('justifications', [
            'id' => $justification->id,
            'status' => 'approved',
            'admin_comment' => 'Certificat médical reçu.',
        ]);
    }

    public function test_admin_can_export_reports_pdf_and_word(): void
    {
        $pdfResponse = $this->actingAs($this->admin)->get('/admin/reports/export-pdf?from=2026-11-01&to=2026-11-23');
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertSee("Test Company");

        $wordResponse = $this->actingAs($this->admin)->get('/admin/reports/export-word?from=2026-11-01&to=2026-11-23');
        $wordResponse->assertStatus(200);
        $wordResponse->assertHeader('Content-Type', 'application/msword; charset=UTF-8');
    }

    public function test_employee_can_get_monthly_report(): void
    {
        $response = $this->actingAs($this->employee, 'sanctum')->getJson('/api/employee/monthly-report?month=2026-11');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        $this->assertNotNull($response->json('report_url'));
    }
}
