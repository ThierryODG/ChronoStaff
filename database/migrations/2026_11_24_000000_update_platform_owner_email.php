<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $ownerEmail = strtolower(config('owner.email'));
        $previousOwner = DB::table('users')
            ->where('email', 'rawelgui01@gmail.com')
            ->where('role', 'superadmin')
            ->first();

        $configuredOwner = DB::table('users')->where('email', $ownerEmail)->first();

        if ($configuredOwner) {
            DB::table('users')->where('id', $configuredOwner->id)->update([
                'company_id' => null,
                'name' => 'Propriétaire Développeur',
                'password' => Hash::make(Str::random(64)),
                'role' => 'superadmin',
                'is_active' => true,
                'updated_at' => now(),
            ]);
        } elseif ($previousOwner) {
            DB::table('users')->where('id', $previousOwner->id)->update([
                'email' => $ownerEmail,
                'company_id' => null,
                'name' => 'Propriétaire Développeur',
                'password' => Hash::make(Str::random(64)),
                'updated_at' => now(),
            ]);
        }

        if ($configuredOwner && $previousOwner && $previousOwner->id !== $configuredOwner->id) {
            DB::table('users')->where('id', $previousOwner->id)->update([
                'role' => 'employee',
                'is_active' => false,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $previousEmail = 'rawelgui01@gmail.com';
        $configuredEmail = strtolower(config('owner.email'));

        if (!DB::table('users')->where('email', $previousEmail)->exists()) {
            DB::table('users')
                ->where('email', $configuredEmail)
                ->where('role', 'superadmin')
                ->update(['email' => $previousEmail, 'updated_at' => now()]);
        }
    }
};
