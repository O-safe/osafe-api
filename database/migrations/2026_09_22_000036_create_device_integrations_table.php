<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // External platform integrations per device (e.g. Google Home, emergency services, custom)
        Schema::create('device_integrations', function (Blueprint $table) {
            $table->id('integration_id');
            $table->unsignedBigInteger('device_id');       // FK -> devices
            $table->string('user_id')->nullable();          // FK -> users.user_id (the authorizing user)
            $table->string('platform', 80);                 // google_home, amazon_alexa, emergency_services, custom
            $table->string('external_device_id')->nullable(); // device ID on external platform
            $table->string('external_account_id')->nullable();
            $table->text('access_token')->nullable();       // encrypted OAuth access token
            $table->text('refresh_token')->nullable();      // encrypted OAuth refresh token
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();             // platform-specific configuration
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'platform']);     // one integration per platform per device
            $table->index('device_id');
            $table->index('user_id');
            $table->index('platform');
            $table->index('is_active');

            $table->foreign('device_id')->references('device_id')->on('devices')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_integrations');
    }
};
