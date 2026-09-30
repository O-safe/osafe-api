<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geofence_events', function (Blueprint $table) {
            $table->id('geofence_event_id');
            $table->unsignedBigInteger('geofence_id');
            $table->unsignedBigInteger('device_id');
            $table->string('user_id')->nullable();           // device owner at time of event
            $table->string('event_type', 20);                // entry, exit, dwell
            $table->decimal('latitude', 10, 8)->nullable();  // location at time of event
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('alert_sent')->default(false);   // was an alert dispatched
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index('geofence_id');
            $table->index('device_id');
            $table->index('user_id');
            $table->index('event_type');
            $table->index('occurred_at');

            $table->foreign('geofence_id')->references('geofence_id')->on('geofences')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofence_events');
    }
};
