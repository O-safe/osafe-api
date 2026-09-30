<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->string('user_id');
            $table->unsignedBigInteger('subscription_id')->nullable(); // FK -> user_subscriptions
            $table->string('reference', 100)->unique();  // internal unique reference
            $table->string('gateway_reference')->nullable(); // external payment gateway txn ID
            $table->string('gateway', 50)->nullable();   // paystack, flutterwave, stripe, etc.
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('type', 30)->default('charge'); // charge, refund, reversal
            $table->string('status', 30)->default('pending'); // pending, successful, failed, reversed
            $table->string('description')->nullable();
            $table->json('gateway_response')->nullable();  // raw gateway payload
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('subscription_id');
            $table->index('reference');
            $table->index('status');
            $table->index('paid_at');

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('subscription_id')->references('subscription_id')->on('user_subscriptions')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_transactions');
    }
};
