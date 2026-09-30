<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historical location records for physical devices.
        // High write-volume table; indexes are critical.
        Schema::create('device_locations', function (Blueprint $table) {
            $table->id('location_id');
            $table->unsignedBigInteger('device_id');       // FK -> devices.device_id
            $table->string('user_id')->nullable();          // FK -> users.user_id (owner at time of record)
            $table->decimal('latitude', 10, 8);             // WGS-84
            $table->decimal('longitude', 11, 8);            // WGS-84
            $table->decimal('accuracy', 8, 2)->nullable();  // metres
            $table->decimal('altitude', 10, 2)->nullable(); // metres above sea level
            $table->decimal('speed', 8, 2)->nullable();     // m/s
            $table->decimal('heading', 6, 2)->nullable();   // 0-360 degrees
            $table->string('source', 30)->default('gps'); // gps, network, fused, manual
            $table->boolean('is_mock')->default(false);    // detect mock location
            $table->string('address')->nullable();          // reverse-geocoded address
            $table->timestamp('recorded_at');               // when device recorded the location
            $table->timestamp('received_at')->useCurrent(); // when server received it

            $table->index('device_id');
            $table->index('user_id');
            $table->index('recorded_at');
            $table->index(['device_id', 'recorded_at']); // most common query pattern

            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_locations');
    }
};
