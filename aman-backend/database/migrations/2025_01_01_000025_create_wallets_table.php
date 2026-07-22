<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('captain_id')->unique()->constrained('captains')->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0); // سالب = عمولات مستحقة
            $table->string('currency', 3)->default('MRU');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
