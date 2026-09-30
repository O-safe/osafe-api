<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id('payment_method_id');
            $table->string('user_id');                   // FK -> users.user_id
            $table->string('type', 50)->default('card'); // card, bank_account, mobile_money
            $table->string('gateway', 50)->nullable();   // paystack, flutterwave, stripe
            $table->string('gateway_token')->nullable(); // tokenized card / auth code
            $table->string('last_four', 4)->nullable();  // last 4 card digits
            $table->string('brand', 30)->nullable();     // visa, mastercard, verve
            $table->string('bank_name')->nullable();
            $table->unsignedTinyInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('country_code', 3)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'is_default']);

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
