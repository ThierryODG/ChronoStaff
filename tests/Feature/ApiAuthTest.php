<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_sanctum_token(): void
    {
        $company = Company::create(['name' => 'Acme Corp']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Alice Martin',
            'email' => 'alice@test.local',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'pin_code' => '4321',
            'qr_token' => 'qr-token-alice',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'alice@test.local',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role', 'pin_code', 'qr_token', 'needs_terms_acceptance'],
            ])
            ->assertJson([
                'user' => [
                    'needs_terms_acceptance' => true,
                ],
            ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::create([
            'name' => 'Banned Bob',
            'email' => 'bob@test.local',
            'password' => Hash::make('secret'),
            'role' => 'employee',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'bob@test.local',
            'password' => 'secret',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_accept_terms(): void
    {
        $user = User::create([
            'name' => 'Alice Martin',
            'email' => 'alice@test.local',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
            'terms_accepted_at' => null,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/legal/accept');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Conditions d\'utilisation et politique de confidentialité acceptées.']);

        $this->assertNotNull($user->fresh()->terms_accepted_at);
    }
}

