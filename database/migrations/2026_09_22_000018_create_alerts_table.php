<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id('alert_id');
            $table->string('user_id');                     // recipient / owner user
            $table->unsignedBigInteger('device_id')->nullable();
            $table->unsignedBigInteger('geofence_id')->nullable();
            $table->unsignedBigInteger('location_event_id')->nullable();
            $table->string('type', 60);                    // sos, geofence_breach, low_battery, device_offline, speed_alert, panic
            $table->string('severity', 20)->default('info'); // info, warning, critical
            $table->string('title', 200);
            $table->text('body');
            $table->string('status', 30)->default('unread'); // unread, read, dismissed, resolved
            $table->boolean('is_read')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->string('resolved_by')->nullable();     // FK -> users.user_id
            $table->timestamp('read_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('triggered_at')->useCurrent();
            $table->timestamps();

            $table->index('user_id');
            $table->index('device_id');
            $table->index('type');
            $table->index('status');
            $table->index('is_read');
            $table->index('triggered_at');

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('geofence_id')->references('geofence_id')->on('geofences')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
