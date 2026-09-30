<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_command_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('command_id');      // FK -> device_commands
            $table->unsignedBigInteger('device_id');       // denormalized for query performance
            $table->string('event', 50);                   // queued, sent, ack, executed, failed, expired
            $table->text('message')->nullable();
            $table->json('response_payload')->nullable();  // device ack payload
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('logged_at')->useCurrent();

            $table->index('command_id');
            $table->index('device_id');
            $table->index('logged_at');

            $table->foreign('command_id')->references('command_id')->on('device_commands')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_command_logs');
    }
};
