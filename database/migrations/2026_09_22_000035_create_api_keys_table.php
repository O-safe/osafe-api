<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // API keys issued BY O SAFE to external integrators or device manufacturers.
        // Distinct from the GlobalApiKey middleware key (APP_API_KEY) which is a single shared secret.
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id('api_key_id');
            $table->string('name', 150);                   // descriptive name for this key
            $table->string('key', 80)->unique();           // the hashed API key value
            $table->string('key_prefix', 10);              // first 8 chars for display / lookup
            $table->string('owner_type', 20)->default('user'); // user, staff, partner
            $table->string('owner_id');                    // user_id or staff_id
            $table->json('scopes')->nullable();             // allowed actions / permissions
            $table->json('allowed_ips')->nullable();        // optional IP whitelist
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('key');
            $table->index('key_prefix');
            $table->index('owner_id');
            $table->index('is_active');
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
