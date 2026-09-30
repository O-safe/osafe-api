<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Significant location events: SOS, zone entry/exit, speed alert etc.
        Schema::create('location_events', function (Blueprint $table) {
            $table->id('event_id');
            $table->unsignedBigInteger('device_id');
            $table->string('user_id')->nullable();
            $table->string('event_type', 50);  // sos, panic, zone_entry, zone_exit, speed_exceeded, low_battery, geofence_breach
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->string('acknowledged_by')->nullable(); // FK -> users.user_id
            $table->timestamp('acknowledged_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index('device_id');
            $table->index('user_id');
            $table->index('event_type');
            $table->index('occurred_at');
            $table->index('is_acknowledged');

            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_events');
    }
};
