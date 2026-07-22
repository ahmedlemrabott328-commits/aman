<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('user_type', 10); // customer|captain
            $table->string('code', 10);
            $table->boolean('is_used')->default(false);
            $table->smallInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['phone', 'user_type', 'is_used']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
