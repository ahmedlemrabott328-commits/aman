<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_location_pings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->double('lat');
            $table->double('lng');
            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['trip_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_location_pings');
    }
};
