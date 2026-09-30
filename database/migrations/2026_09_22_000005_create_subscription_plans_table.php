<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id('plan_id');
            $table->string('name', 100);                    // e.g. Standard, Family, Pro
            $table->string('slug', 100)->unique();          // machine-readable key
            $table->text('description')->nullable();
            $table->unsignedInteger('max_devices')->default(1);
            $table->unsignedInteger('max_family_members')->default(1);
            $table->unsignedInteger('location_history_days')->default(30); // how many days of history
            $table->decimal('price_monthly', 10, 2)->default(0.00);
            $table->decimal('price_yearly', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('NGN');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // highlight in UI
            $table->json('features')->nullable();           // JSON array of feature flags
            $table->unsignedBigInteger('sort_order')->default(0);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
