<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id('command_id');
            $table->unsignedBigInteger('device_id');       // FK -> devices
            $table->string('issued_by');                    // FK -> users.user_id or staff.staff_id
            $table->string('issued_by_type', 20)->default('user'); // user, staff
            $table->string('command_type', 80);             // lock, unlock, ring, locate, wipe, reboot, sos_cancel
            $table->string('status', 30)->default('pending'); // pending, sent, delivered, executed, failed, cancelled
            $table->string('priority', 20)->default('normal'); // low, normal, high, critical
            $table->json('payload')->nullable();             // command parameters
            $table->text('notes')->nullable();
            $table->timestamp('scheduled_at')->nullable();  // for deferred commands
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('device_id');
            $table->index('issued_by');
            $table->index('status');
            $table->index('command_type');
            $table->index(['device_id', 'status']);

            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
