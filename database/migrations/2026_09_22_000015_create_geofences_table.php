<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geofences', function (Blueprint $table) {
            $table->id('geofence_id');
            $table->string('owner_user_id');              // FK -> users.user_id
            $table->unsignedBigInteger('family_id')->nullable(); // FK -> families (optional family scope)
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->decimal('center_latitude', 10, 8);    // WGS-84 center
            $table->decimal('center_longitude', 11, 8);
            $table->unsignedInteger('radius_meters')->default(100); // radius in metres
            $table->string('shape', 20)->default('circle'); // circle (polygon support in future)
            $table->boolean('alert_on_entry')->default(true);
            $table->boolean('alert_on_exit')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('color', 10)->nullable();       // hex color for UI
            $table->time('active_from')->nullable();        // time-based activation (e.g. 09:00)
            $table->time('active_until')->nullable();
            $table->json('active_days')->nullable();        // e.g. ["mon","tue","wed"]
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_user_id');
            $table->index('family_id');
            $table->index('is_active');

            $table->foreign('owner_user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('family_id')->references('family_id')->on('families')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofences');
    }
};
