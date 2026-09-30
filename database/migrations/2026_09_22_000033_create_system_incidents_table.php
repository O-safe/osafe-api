<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_incidents', function (Blueprint $table) {
            $table->id('incident_id');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('severity', 20)->default('warning'); // info, warning, critical, outage
            $table->string('status', 30)->default('investigating'); // investigating, identified, monitoring, resolved
            $table->string('affected_services')->nullable(); // comma-separated service names
            $table->string('reported_by')->nullable();     // staff_id
            $table->string('resolved_by')->nullable();     // staff_id
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('severity');
            $table->index('status');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_incidents');
    }
};
