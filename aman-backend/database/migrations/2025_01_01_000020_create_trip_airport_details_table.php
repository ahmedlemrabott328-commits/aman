<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_airport_details', function (Blueprint $table) {
            $table->foreignId('trip_id')->primary()->constrained('trips')->cascadeOnDelete();
            $table->string('flight_number', 20)->nullable();
            $table->string('terminal', 20)->nullable();
            $table->boolean('is_pickup_from_airport')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_airport_details');
    }
};
