<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('city_id')->constrained('cities');
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types');
            $table->decimal('base_fare', 10, 2)->default(0);
            $table->decimal('price_per_km', 10, 2)->default(0);
            $table->decimal('price_per_minute', 10, 2)->default(0);
            $table->decimal('min_fare', 10, 2)->default(0);
            $table->decimal('cancellation_fee', 10, 2)->default(0);
            $table->string('currency', 3)->default('MRU');
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
