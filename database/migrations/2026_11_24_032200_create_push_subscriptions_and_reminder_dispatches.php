<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('endpoint_hash', 64)->unique();
            $table->text('endpoint');
            $table->text('public_key');
            $table->text('auth_token');
            $table->string('content_encoding', 20)->default('aes128gcm');
            $table->timestamps();
        });

        Schema::create('reminder_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('reminder_date');
            $table->string('reminder_time', 5);
            $table->char('message_hash', 64);
            $table->string('message', 200);
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'reminder_date', 'reminder_time', 'message_hash'],
                'reminder_dispatches_daily_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_dispatches');
        Schema::dropIfExists('push_subscriptions');
    }
};
