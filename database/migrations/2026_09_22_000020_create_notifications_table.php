<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Application notifications delivered to users (not the Laravel notifications table)
        // Named osafe_notifications to avoid conflict with Laravel's built-in notifications table
        Schema::create('osafe_notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->string('user_id');                    // recipient
            $table->unsignedBigInteger('alert_id')->nullable(); // FK -> alerts
            $table->unsignedBigInteger('template_id')->nullable(); // FK -> notification_templates
            $table->string('channel', 30)->default('push'); // push, email, sms, in_app
            $table->string('title', 200)->nullable();
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_sent')->default(false);
            $table->string('status', 30)->default('pending'); // pending, sent, delivered, failed
            $table->string('failure_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('alert_id');
            $table->index('channel');
            $table->index('is_read');
            $table->index('status');

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('alert_id')->references('alert_id')->on('alerts')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osafe_notifications');
    }
};
