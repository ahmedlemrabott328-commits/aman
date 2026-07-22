<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services');
            $table->string('code', 30);
            $table->string('name_ar', 100);
            $table->string('name_fr', 100);
            $table->string('name_en', 100);
            $table->smallInteger('capacity')->default(4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['service_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
