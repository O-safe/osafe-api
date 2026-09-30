<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->string('requested_by');               // user_id or staff_id
            $table->string('requested_by_type', 20)->default('user');
            $table->string('type', 80);                   // location_history, alert_summary, geofence_activity, billing
            $table->string('title', 200)->nullable();
            $table->string('status', 30)->default('pending'); // pending, processing, ready, failed
            $table->string('file_path')->nullable();       // path to generated file
            $table->string('file_format', 10)->nullable(); // pdf, csv, xlsx
            $table->json('parameters')->nullable();        // report filters/parameters
            $table->string('failure_reason')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();   // auto-delete after expiry
            $table->timestamps();

            $table->index('requested_by');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
