<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('cooldown_enabled')->default(true)->after('break_deduction_minutes');
            $table->integer('min_punch_interval_minutes')->default(60)->after('cooldown_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['cooldown_enabled', 'min_punch_interval_minutes']);
        });
    }
};
