<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('trip_code', 20)->unique();

            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('captain_id')->nullable()->constrained('captains');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles');

            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('city_id')->constrained('cities');

            $table->string('trip_mode', 20)->default('instant'); // instant|scheduled|open
            $table->string('status', 20)->default('requested');

            $table->text('pickup_address');
            $table->double('pickup_lat');
            $table->double('pickup_lng');

            $table->text('dropoff_address')->nullable();
            $table->double('dropoff_lat')->nullable();
            $table->double('dropoff_lng')->nullable();

            $table->timestamp('scheduled_at')->nullable();

            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('duration_min')->nullable();

            $table->decimal('estimated_price', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('MRU');

            $table->foreignId('pricing_rule_id')->nullable()->constrained('pricing_rules');
            $table->foreignId('commission_rule_id')->nullable()->constrained('commission_rules');
            $table->decimal('commission_amount', 10, 2)->nullable();

            $table->string('cancelled_by_type', 10)->nullable();
            $table->unsignedBigInteger('cancelled_by_id')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('service_id');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
