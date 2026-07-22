<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('captain_id')->constrained('captains')->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types');
            $table->string('plate_number', 20)->unique();
            $table->string('brand', 50)->nullable();
            $table->string('model', 50)->nullable();
            $table->smallInteger('year')->nullable();
            $table->string('color', 30)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
