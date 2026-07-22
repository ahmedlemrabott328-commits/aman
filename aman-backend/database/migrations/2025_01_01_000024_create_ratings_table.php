<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->unique()->constrained('trips')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('captain_id')->constrained('captains');
            $table->smallInteger('score'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('captain_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
