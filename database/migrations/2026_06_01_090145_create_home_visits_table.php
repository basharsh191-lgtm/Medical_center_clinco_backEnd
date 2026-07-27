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
    Schema::create('home_visits', function (Blueprint $table) {
        $table->id();
        $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
        $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
        $table->foreignId('doctor_id')->nullable()->constrained('doctors')->cascadeOnDelete();
        $table->dateTime('visit_date');
        $table->time('start_time');
        $table->time('end_time');
        $table->decimal('location_lat', 10, 8);
        $table->decimal('location_lng', 11, 8);

        // أضفنا: accepted, arrived, rejected
        $table->enum('status', [
            'pending',
            'assigned',
            'accepted',
            'rejected',
            'on_the_way',
            'arrived',
            'completed',
            'cancelled'
        ])->default('pending');

        $table->string('rejection_reason')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_visits');
    }
};
