<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captains', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('phone', 20)->unique();
            $table->string('full_name', 150);
            $table->string('email', 150)->nullable();
            $table->string('national_id', 50)->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->text('avatar_url')->nullable();
            $table->string('preferred_lang', 2)->default('ar');
            $table->string('approval_status', 20)->default('pending'); // pending|approved|rejected|suspended
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_online')->default(false);
            $table->double('current_lat')->nullable();
            $table->double('current_lng')->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(5.00);
            $table->integer('rating_count')->default(0);
            $table->text('fcm_token')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_online', 'approval_status', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captains');
    }
};
