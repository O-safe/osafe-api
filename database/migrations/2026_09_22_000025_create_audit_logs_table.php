<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O SAFE administrative audit trail.
        // Distinct from activity_logs (admin staff portal read-activity log).
        // This table is for security-critical operations: device activation, billing, account changes.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id('audit_log_id');
            $table->string('actor_id');                    // user_id or staff_id
            $table->string('actor_type', 20);              // user, staff, system
            $table->string('action', 100);                 // e.g. device.activated, subscription.cancelled
            $table->string('resource_type', 100)->nullable(); // e.g. Device, UserSubscription
            $table->string('resource_id')->nullable();     // ID of the affected resource
            $table->json('before')->nullable();            // state before change
            $table->json('after')->nullable();             // state after change
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index('actor_id');
            $table->index('action');
            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
