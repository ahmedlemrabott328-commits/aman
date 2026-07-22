<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captain_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('captain_id')->constrained('captains')->cascadeOnDelete();
            $table->string('document_type', 50); // license|id_card|vehicle_registration|insurance
            $table->text('file_url');
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captain_documents');
    }
};
