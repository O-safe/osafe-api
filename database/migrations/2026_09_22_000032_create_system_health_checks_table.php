<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_health_checks', function (Blueprint $table) {
            $table->id('health_check_id');
            $table->string('service', 80);                 // database, redis, queue, storage, push_gateway
            $table->string('status', 20)->default('healthy'); // healthy, degraded, down
            $table->decimal('response_time_ms', 10, 2)->nullable();
            $table->string('message')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('checked_at')->useCurrent();

            $table->index('service');
            $table->index('status');
            $table->index('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_checks');
    }
};
