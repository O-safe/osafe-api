<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Granular per-member permissions within a family
        // e.g. can_view_location, can_manage_geofences, can_view_alerts
        Schema::create('family_member_permissions', function (Blueprint $table) {
            $table->id('permission_id');
            $table->unsignedBigInteger('family_member_id'); // FK -> family_members
            $table->string('permission_key', 100);          // e.g. 'view_location', 'manage_geofences'
            $table->boolean('granted')->default(true);
            $table->string('granted_by')->nullable();        // FK -> users.user_id
            $table->timestamps();

            $table->unique(['family_member_id', 'permission_key'], 'fam_member_perm_unique');
            $table->index('family_member_id');

            $table->foreign('family_member_id')->references('family_member_id')->on('family_members')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_member_permissions');
    }
};
