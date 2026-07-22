<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_dispatch_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('captain_id')->constrained('captains');
            $table->decimal('search_radius_km', 6, 2);
            $table->string('status', 15)->default('offered'); // offered|accepted|rejected|timeout
            $table->timestamp('offered_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();

            $table->index('trip_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_dispatch_attempts');
    }
};
