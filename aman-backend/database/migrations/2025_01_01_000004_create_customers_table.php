<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('phone', 20)->unique();
            $table->string('full_name', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('preferred_lang', 2)->default('ar');
            $table->text('avatar_url')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('fcm_token')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(5.00);
            $table->integer('rating_count')->default(0);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
