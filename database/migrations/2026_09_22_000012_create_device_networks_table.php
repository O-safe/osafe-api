<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stores network/connectivity snapshots for a device.
        Schema::create('device_networks', function (Blueprint $table) {
            $table->id('network_id');
            $table->unsignedBigInteger('device_id');
            $table->string('type', 30)->nullable();      // wifi, cellular, ethernet, bluetooth
            $table->string('carrier', 100)->nullable();  // mobile network operator
            $table->string('ssid')->nullable();          // wifi network name
            $table->string('signal_strength', 20)->nullable(); // excellent, good, poor, none
            $table->unsignedTinyInteger('signal_dbm')->nullable(); // raw dBm
            $table->boolean('is_roaming')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            $table->index('device_id');
            $table->index('recorded_at');

            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_networks');
    }
};
