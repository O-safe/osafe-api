<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot: which devices are monitored by which geofences
        Schema::create('geofence_device', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('geofence_id');
            $table->unsignedBigInteger('device_id');
            $table->timestamps();

            $table->unique(['geofence_id', 'device_id']);
            $table->index('geofence_id');
            $table->index('device_id');

            $table->foreign('geofence_id')->references('geofence_id')->on('geofences')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofence_device');
    }
};
