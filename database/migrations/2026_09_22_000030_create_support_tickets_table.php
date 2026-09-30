<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->string('ticket_number', 20)->unique(); // e.g. TKT-20260922-0001
            $table->string('user_id');                     // submitting user
            $table->string('assigned_to')->nullable();     // staff.staff_id
            $table->string('subject', 200);
            $table->text('description');
            $table->string('category', 80)->default('general'); // general, billing, device, account, geofence
            $table->string('priority', 20)->default('medium');  // low, medium, high, critical
            $table->string('status', 30)->default('open');      // open, in_progress, waiting_user, resolved, closed
            $table->string('resolution_note')->nullable();
            $table->unsignedBigInteger('device_id')->nullable(); // associated physical device if applicable
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('priority');
            $table->index('ticket_number');

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
