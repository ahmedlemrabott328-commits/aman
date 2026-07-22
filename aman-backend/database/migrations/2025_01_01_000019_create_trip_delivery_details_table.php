<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_delivery_details', function (Blueprint $table) {
            $table->foreignId('trip_id')->primary()->constrained('trips')->cascadeOnDelete();
            $table->string('receiver_name', 150);
            $table->string('receiver_phone', 20);
            $table->text('package_description')->nullable();
            $table->string('package_size', 20)->nullable(); // small|medium|large
            $table->text('delivery_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_delivery_details');
    }
};
