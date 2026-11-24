<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('work_start_time', 5)->default('08:00')->after('timezone');
            $table->string('work_end_time', 5)->default('17:00')->after('work_start_time');
            $table->boolean('overtime_enabled')->default(true)->after('work_end_time');
            $table->boolean('require_onsite_for_reports')->default(true)->after('overtime_enabled');
            $table->json('reminders')->nullable()->after('reminder_message');
        });

        Schema::create('justifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('type')->default('retard'); // 'retard', 'absence', 'autre'
            $table->text('reason');
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->text('admin_comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justifications');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'work_start_time',
                'work_end_time',
                'overtime_enabled',
                'require_onsite_for_reports',
                'reminders',
            ]);
        });
    }
};
