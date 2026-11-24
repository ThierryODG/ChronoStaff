<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Mail\OwnerLoginCodeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WebAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'ChronoStaff Test HQ',
            'latitude' => 48.8698,
            'longitude' => 2.3314,
        ]);

        $this->admin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Patron Test',
            'email' => 'admin@test.local',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_login_via_web(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@test.local',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_admin_can_login_via_admin_login_url(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.local',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_owner_entry_at_admin_path_is_separate_from_company_login(): void
    {
        $owner = User::create([
            'company_id' => null,
            'name' => 'Propriétaire ChronoStaff',
            'email' => 'rawelgui02@gmail.com',
            'password' => Hash::make('owner-secret'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        config(['mail.default' => 'smtp']);
        Mail::fake();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Espace propriétaire')
            ->assertSee('rawelgui02@gmail.com')
            ->assertDontSee('Mot de passe');

        $this->post('/admin', [
            'email' => $this->admin->email,
            'password' => 'secret123',
        ])->assertSessionHas('status', 'Si cette adresse correspond au compte propriétaire, un code temporaire a été envoyé. Vérifiez aussi vos courriers indésirables.')
            ->assertSessionHas('owner_login_email', $this->admin->email);
        $this->assertGuest();
        Mail::assertNothingSent();

        $this->post('/admin', ['email' => 'rawelgui01@gmail.com'])
            ->assertSessionHas('status', 'Si cette adresse correspond au compte propriétaire, un code temporaire a été envoyé. Vérifiez aussi vos courriers indésirables.');
        Mail::assertNothingSent();

        $this->post('/admin', [
            'email' => $owner->email,
        ])->assertSessionHas('owner_login_email', $owner->email)
            ->assertSessionHas('status', 'Si cette adresse correspond au compte propriétaire, un code temporaire a été envoyé. Vérifiez aussi vos courriers indésirables.');

        $code = null;
        Mail::assertSent(OwnerLoginCodeMail::class, function (OwnerLoginCodeMail $mail) use (&$code, $owner) {
            $code = $mail->code;

            return $mail->hasTo($owner->email)
                && str_contains($mail->render(), $code);
        });
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $wrongCode = $code === '000000' ? '000001' : '000000';
        $this->post(route('superadmin.verify-code'), ['code' => $wrongCode])
            ->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post(route('superadmin.verify-code'), ['code' => $code])
            ->assertRedirect(route('superadmin.companies.index'));

        $this->assertAuthenticatedAs($owner);
        $this->get(route('superadmin.companies.index'))->assertOk();
        $this->get(route('superadmin.subscriptions.index'))->assertOk();
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_landing_page_offers_company_login_without_owner_login_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Espace Client')
            ->assertSee('href="' . route('login') . '"', false)
            ->assertSee('mailto:rawelgui02@gmail.com', false)
            ->assertDontSee('href="' . route('superadmin.login') . '"', false);
    }

    public function test_owner_code_is_not_sent_to_the_log_mailer(): void
    {
        User::create([
            'company_id' => null,
            'name' => 'Propriétaire ChronoStaff',
            'email' => 'rawelgui02@gmail.com',
            'password' => Hash::make('unused-password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);
        config(['mail.default' => 'log']);
        Mail::fake();

        $this->post('/admin', ['email' => 'rawelgui02@gmail.com'])
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
        $this->assertGuest();
    }

    public function test_superadmin_can_create_a_company(): void
    {
        $owner = User::create([
            'company_id' => null,
            'name' => 'Propriétaire ChronoStaff',
            'email' => 'rawelgui02@gmail.com',
            'password' => Hash::make('unused-password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('superadmin.companies.store'), [
                'company_name' => 'Entreprise de test',
                'admin_name' => 'Administrateur test',
                'admin_email' => 'company-admin@test.local',
                'admin_password' => 'company-secret',
            ])
            ->assertRedirect();

        $company = Company::where('name', 'Entreprise de test')->firstOrFail();
        $this->assertDatabaseHas('departments', [
            'company_id' => $company->id,
            'name' => 'Général',
        ]);
        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'company-admin@test.local',
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_access_dashboard_and_views(): void
    {
        $dept = Department::create(['company_id' => $this->company->id, 'name' => 'Technique']);

        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Ouvrir le menu du compte');
        $response->assertSee('Profil et paramètres');
        $response->assertSee('Ouvrir l\'application mobile', false);

        $empResponse = $this->actingAs($this->admin)->get('/admin/employees');
        $empResponse->assertStatus(200);

        $deptResponse = $this->actingAs($this->admin)->get('/admin/departments');
        $deptResponse->assertStatus(200);

        $repResponse = $this->actingAs($this->admin)->get('/admin/reports');
        $repResponse->assertStatus(200);

        $settingsResponse = $this->actingAs($this->admin)->get('/admin/company/settings');
        $settingsResponse->assertStatus(200);
    }
}

