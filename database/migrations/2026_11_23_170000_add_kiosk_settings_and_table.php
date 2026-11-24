<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('kiosk_mode_enabled')->default(true);
            $table->boolean('individual_login_enabled')->default(true);
        });

        Schema::create('kiosks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('device_code', 32)->unique();
            $table->string('access_key');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kiosks');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['kiosk_mode_enabled', 'individual_login_enabled']);
        });
    }
};

