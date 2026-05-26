<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('appointments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
        $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
        $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
        $table->date('appointment_date');
        $table->time('start_time');
        $table->time('end_time');
        $table->enum('status', ['scheduled', 'arrived', 'completed', 'cancelled'])->default('scheduled');
        $table->text('notes')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
