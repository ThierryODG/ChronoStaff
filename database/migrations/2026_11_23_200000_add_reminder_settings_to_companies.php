<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('reminder_time', 5)->default('07:00')->after('individual_login_enabled');
            $table->string('reminder_message', 500)->default('N\'oubliez pas de badger votre arrivée !')->after('reminder_time');
            $table->boolean('reminder_enabled')->default(true)->after('reminder_message');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['reminder_time', 'reminder_message', 'reminder_enabled']);
        });
    }
};
