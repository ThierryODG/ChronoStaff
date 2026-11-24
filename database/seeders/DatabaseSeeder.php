<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial setup.
     * Note: Creates only the initial company and admin required for first boot.
     */
    public function run(): void
    {
        // 1. Initial Company
        $company = Company::firstOrCreate(
            ['name' => 'ChronoStaff HQ'],
            [
                'address' => '10 Rue de la Paix, 75002 Paris, France',
                'latitude' => 48.8698,
                'longitude' => 2.3314,
                'time_rounding' => 'minute',
                'break_threshold_hours' => 6.00,
                'break_deduction_minutes' => 30,
                'timezone' => 'Europe/Paris',
            ]
        );

        // 2. Initial Admin Account
        $admin = User::firstOrCreate(
            ['email' => 'admin@chronostaff.local'],
            [
                'company_id' => $company->id,
                'name' => 'Directeur Général',
                'password' => Hash::make('Admin@123456'),
                'role' => 'admin',
                'is_active' => true,
                'terms_accepted_at' => now(),
            ]
        );

        // 2b. Super-Admin (Propriétaire Développeur de la plateforme ChronoStaff)
        User::updateOrCreate(
            ['email' => config('owner.email')],
            [
                'company_id' => null,
                'name' => 'Propriétaire Développeur',
                'password' => Hash::make(Str::random(64)),
                'role' => 'superadmin',
                'is_active' => true,
                'terms_accepted_at' => now(),
            ]
        );

        // 3. Initial Department
        $defaultDept = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Général']
        );

        // 4. Initial Test Employee (useful for instant Postman testing)
        $employeeCredentials = User::generateCredentials();
        User::firstOrCreate(
            ['email' => 'employe@chronostaff.local'],
            [
                'company_id' => $company->id,
                'name' => 'Jean Dupont',
                'password' => Hash::make('Employe@123456'),
                'role' => 'employee',
                'pin_code' => '1234', // Fixed PIN for ease of testing
                'qr_token' => 'EMP-QR-JEAN-DUPONT-TEST-TOKEN-001',
                'is_active' => true,
                'terms_accepted_at' => null, // Test legal acceptance screen
            ]
        );

        // 5. Initial Test Manager
        User::firstOrCreate(
            ['email' => 'manager@chronostaff.local'],
            [
                'company_id' => $company->id,
                'name' => 'Marc Tremblay (Manager)',
                'password' => Hash::make('Manager@123456'),
                'role' => 'manager',
                'is_active' => true,
                'terms_accepted_at' => now(),
            ]
        );

        // 6. Initial Test Kiosk / Borne partagée (identifiants prêts pour tester)
        \App\Models\Kiosk::firstOrCreate(
            ['device_code' => 'KIOSK-DEMO-001'],
            [
                'company_id' => $company->id,
                'name' => 'Borne Accueil (Démo)',
                'access_key' => Hash::make('123456'),
                'is_active' => true,
            ]
        );
    }
}
