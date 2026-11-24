<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'chronostaff:create-admin 
                            {email? : The admin email} 
                            {--name= : The admin name} 
                            {--password= : The admin password}';

    protected $description = 'Create a new administrator account for ChronoStaff';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Admin Email');
        $name = $this->option('name') ?? $this->ask('Admin Name', 'Administrateur');
        $password = $this->option('password') ?? $this->secret('Password');

        if (!$email || !$password) {
            $this->error('Email and password are required.');
            return Command::FAILURE;
        }

        $company = Company::firstOrCreate(
            ['id' => 1],
            ['name' => 'ChronoStaff Company']
        );

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'company_id' => $company->id,
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
                'terms_accepted_at' => now(),
            ]
        );

        $this->info("Admin user '{$admin->email}' successfully created/updated!");
        return Command::SUCCESS;
    }
}

