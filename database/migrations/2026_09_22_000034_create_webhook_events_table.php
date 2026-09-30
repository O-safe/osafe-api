<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id('webhook_event_id');
            $table->string('event_id', 100)->unique();     // idempotency key from external source
            $table->string('source', 80);                  // paystack, flutterwave, device_gateway, etc.
            $table->string('event_type', 100);             // e.g. payment.success, device.sos
            $table->json('payload');                       // raw webhook payload
            $table->string('status', 30)->default('received'); // received, processing, processed, failed, ignored
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('failure_reason')->nullable();
            $table->string('ip_address', 45)->nullable();  // source IP
            $table->string('signature')->nullable();       // HMAC signature for verification
            $table->boolean('signature_verified')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('event_id');
            $table->index('source');
            $table->index('event_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
