<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-user security preferences
        Schema::create('security_settings', function (Blueprint $table) {
            $table->id('security_setting_id');
            $table->string('user_id')->unique();           // one record per user
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_method', 30)->nullable();  // totp, sms, email
            $table->boolean('login_notification_enabled')->default(true);
            $table->boolean('new_device_alert_enabled')->default(true);
            $table->boolean('suspicious_activity_alert')->default(true);
            $table->unsignedTinyInteger('max_login_devices')->default(5);
            $table->json('trusted_ip_ranges')->nullable(); // optional IP whitelist
            $table->timestamps();

            $table->index('user_id');

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_settings');
    }
};
