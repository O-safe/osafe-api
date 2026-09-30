<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id('subscription_id');
            $table->string('user_id');                     // FK -> users.user_id
            $table->unsignedBigInteger('plan_id');         // FK -> subscription_plans
            $table->string('billing_interval', 20)->default('monthly'); // monthly, yearly
            $table->string('status', 30)->default('pending'); // pending, active, cancelled, expired, past_due, trialing
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->string('payment_reference')->nullable(); // external payment gateway reference
            $table->json('metadata')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('plan_id');
            $table->index('status');
            $table->index('ends_at');

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('plan_id')->references('plan_id')->on('subscription_plans')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
