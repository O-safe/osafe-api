<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_members', function (Blueprint $table) {
            $table->id('family_member_id');
            $table->unsignedBigInteger('family_id');     // FK -> families.family_id
            $table->string('user_id');                   // FK -> users.user_id
            $table->string('role', 50)->default('member'); // owner, admin, member, guardian, child
            $table->string('nickname')->nullable();       // display name within the family
            $table->string('relationship')->nullable();   // e.g. parent, child, spouse
            $table->timestamp('joined_at')->nullable();
            $table->unsignedBigInteger('status_id')->default(1); // FK -> setup_statuses (active/suspended)
            $table->string('added_by')->nullable();       // FK -> users.user_id (who added them)
            $table->timestamps();

            $table->unique(['family_id', 'user_id']);     // user can belong to family only once
            $table->index('family_id');
            $table->index('user_id');
            $table->index('status_id');

            $table->foreign('family_id')->references('family_id')->on('families')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
