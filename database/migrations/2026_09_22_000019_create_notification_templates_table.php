<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id('template_id');
            $table->string('slug', 100)->unique();        // machine key, e.g. 'geofence.breach'
            $table->string('name', 150);
            $table->string('channel', 30)->default('push'); // push, email, sms, in_app
            $table->string('subject')->nullable();         // email subject
            $table->text('body');                          // template body (supports placeholders)
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index(['slug', 'channel']);
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
