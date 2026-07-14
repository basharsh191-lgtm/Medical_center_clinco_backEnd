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
Schema::create('medical_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('appointment_id')->nullable()->constrained('appointments')->cascadeOnDelete();
        $table->foreignId('home_visit_id')->nullable()->constrained('home_visits')->cascadeOnDelete();
        $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
        $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
        $table->text('diagnosis')->comment('التشخيص الطيبي');
        $table->text('chief_complaint')->comment('الشكوى الرئيسية للمريض');
        $table->text('notes')->nullable()->comment('ملاحظات إضافية');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
