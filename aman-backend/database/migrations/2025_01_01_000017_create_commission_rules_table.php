<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('city_id')->nullable()->constrained('cities'); // null = كل المدن
            $table->string('commission_type', 10); // percentage|fixed
            $table->decimal('value', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
