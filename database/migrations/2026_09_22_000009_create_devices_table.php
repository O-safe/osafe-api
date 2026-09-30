<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Physical O SAFE monitoring/tracking device.
        // DISTINCT from user_devices (trusted login/application devices).
        Schema::create('devices', function (Blueprint $table) {
            $table->id('device_id');
            $table->string('serial_number', 100)->unique();     // physical serial
            $table->string('imei', 20)->unique()->nullable();   // IMEI for cellular devices
            $table->string('mac_address', 17)->unique()->nullable();
            $table->string('model', 100)->nullable();           // device model name
            $table->string('manufacturer', 100)->nullable();
            $table->string('hardware_version', 50)->nullable();
            $table->string('firmware_version', 50)->nullable();
            $table->string('os_type', 50)->nullable();          // android, ios, rtos, embedded
            $table->string('os_version', 50)->nullable();
            $table->string('platform', 50)->nullable();         // wear, phone, gps-tracker, panic-button
            $table->string('name', 100)->nullable();            // user-assigned display name
            $table->string('color', 30)->nullable();
            $table->string('connectivity')->default('wifi');    // wifi, cellular, bluetooth, lte
            $table->string('status', 30)->default('unactivated'); // unactivated, active, inactive, suspended, decommissioned
            $table->boolean('is_activated')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->decimal('battery_level', 5, 2)->nullable(); // 0.00 - 100.00
            $table->string('battery_status', 20)->nullable();   // charging, discharging, full, unknown
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ip_address', 45)->nullable();
            $table->string('push_token')->nullable();           // FCM/APNS push token
            $table->json('metadata')->nullable();               // extensible device-specific data
            $table->string('registered_by')->nullable();        // FK -> users.user_id (who first registered it)
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('serial_number');
            $table->index('status');
            $table->index('is_activated');
            $table->index('is_online');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
