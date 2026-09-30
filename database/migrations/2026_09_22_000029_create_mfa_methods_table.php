<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfa_methods', function (Blueprint $table) {
            $table->id('mfa_method_id');
            $table->string('user_id');                     // FK -> users.user_id
            $table->string('type', 30);                    // totp, sms, email, backup_codes
            $table->text('secret')->nullable();             // encrypted TOTP secret
            $table->json('backup_codes')->nullable();       // hashed backup codes
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type']);            // one of each type per user
            $table->index('user_id');

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_methods');
    }
};
