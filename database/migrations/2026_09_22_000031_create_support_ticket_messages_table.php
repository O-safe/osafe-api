<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('ticket_id');       // FK -> support_tickets
            $table->string('sender_id');                   // user_id or staff_id
            $table->string('sender_type', 20)->default('user'); // user, staff, system
            $table->text('body');
            $table->json('attachments')->nullable();       // file paths / URLs
            $table->boolean('is_internal')->default(false); // internal staff notes not shown to user
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('sender_id');

            $table->foreign('ticket_id')->references('ticket_id')->on('support_tickets')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};
