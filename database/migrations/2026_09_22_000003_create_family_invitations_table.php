<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_invitations', function (Blueprint $table) {
            $table->id('invitation_id');
            $table->unsignedBigInteger('family_id');
            $table->string('invited_by');                // FK -> users.user_id
            $table->string('invitee_email')->nullable(); // invitation by email
            $table->string('invitee_phone')->nullable(); // invitation by phone
            $table->string('invitee_user_id')->nullable(); // FK -> users if already a user
            $table->string('token', 64)->unique();       // unique invitation token
            $table->string('role', 50)->default('member'); // role to be assigned on acceptance
            $table->string('status', 30)->default('pending'); // pending, accepted, declined, expired, cancelled
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('family_id');
            $table->index('invited_by');
            $table->index('token');
            $table->index('invitee_email');
            $table->index('status');

            $table->foreign('family_id')->references('family_id')->on('families')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('invited_by')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_invitations');
    }
};
