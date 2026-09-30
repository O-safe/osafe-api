<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id('family_id');
            $table->string('owner_user_id');             // FK -> users.user_id
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('avatar')->nullable();        // family profile picture
            $table->string('invite_code', 16)->unique()->nullable(); // shareable join code
            $table->unsignedTinyInteger('max_members')->default(10);
            $table->unsignedBigInteger('status_id')->default(1); // FK -> setup_statuses
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_user_id');
            $table->index('status_id');
            $table->index('invite_code');

            $table->foreign('owner_user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('status_id')->references('status_id')->on('setup_statuses')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
