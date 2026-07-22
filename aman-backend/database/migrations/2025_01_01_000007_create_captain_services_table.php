<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captain_services', function (Blueprint $table) {
            $table->foreignId('captain_id')->constrained('captains')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->primary(['captain_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captain_services');
    }
};
