<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id('preference_id');
            $table->string('user_id');                     // FK -> users.user_id
            $table->string('notification_type', 100);      // e.g. 'geofence_breach', 'sos'
            $table->string('channel', 30)->default('push'); // push, email, sms
            $table->boolean('enabled')->default(true);
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_from')->nullable();         // e.g. 22:00
            $table->time('quiet_until')->nullable();        // e.g. 07:00
            $table->timestamps();

            $table->unique(['user_id', 'notification_type', 'channel'], 'notif_pref_user_type_channel_unique');
            $table->index('user_id');

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
