<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_status_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('device_id');   // FK -> devices.device_id
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->string('changed_by')->nullable();  // user_id or staff_id who triggered change
            $table->string('changed_by_type', 20)->nullable(); // user, staff, system
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index('device_id');
            $table->index('changed_at');

            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_status_history');
    }
};
