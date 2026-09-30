<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Photos, audio, or other media captured by devices (e.g. panic button camera)
        Schema::create('device_media', function (Blueprint $table) {
            $table->id('media_id');
            $table->unsignedBigInteger('device_id');
            $table->string('user_id')->nullable();
            $table->unsignedBigInteger('command_id')->nullable(); // link to triggering command
            $table->unsignedBigInteger('alert_id')->nullable();   // link to associated alert
            $table->string('type', 30)->default('image');         // image, audio, video
            $table->string('filename');
            $table->string('disk', 30)->default('local');         // storage disk
            $table->string('path');                               // storage path
            $table->string('mime_type', 60)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('status', 30)->default('pending');     // pending, uploaded, failed
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index('device_id');
            $table->index('user_id');
            $table->index('command_id');
            $table->index('alert_id');
            $table->index('type');

            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('command_id')->references('command_id')->on('device_commands')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('alert_id')->references('alert_id')->on('alerts')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_media');
    }
};
