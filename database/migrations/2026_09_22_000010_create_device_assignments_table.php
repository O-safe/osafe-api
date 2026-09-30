<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tracks which user a physical device is assigned to, with full history.
        // A device may be reassigned over time; only one record should be 'active' at a time.
        Schema::create('device_assignments', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->unsignedBigInteger('device_id');    // FK -> devices.device_id
            $table->string('user_id');                  // FK -> users.user_id (assigned user)
            $table->unsignedBigInteger('family_id')->nullable(); // FK -> families (associated family)
            $table->string('assigned_by')->nullable();  // FK -> users.user_id or staff.staff_id
            $table->string('assigned_by_type', 20)->nullable(); // 'user' or 'staff'
            $table->string('status', 30)->default('active'); // active, revoked, transferred
            $table->timestamp('assigned_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('device_id');
            $table->index('user_id');
            $table->index('family_id');
            $table->index('status');
            $table->index(['device_id', 'status']);

            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('family_id')->references('family_id')->on('families')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_assignments');
    }
};
