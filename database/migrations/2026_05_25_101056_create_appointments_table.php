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
        $table->enum('status', ['scheduled', 'arrived', 'completed', 'cancelled','no_show'])->default('scheduled');
        $table->text('notes')->nullable();
        $table->timestamps();
    });
    // Schema::create('appointments', function (Blueprint $table) {
    //         $table->id();

    // العلاقات الأساسية
    //         $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
    //         $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
    //         $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();

    // تفاصيل الوقت والتاريخ
    //         $table->date('appointment_date');
    //         $table->time('start_time');
    //         $table->time('end_time');

    // حالة الموعد (تغطي كافة السيناريوهات الحالية والجديدة)
    //         $table->enum('status', ['scheduled', 'arrived', 'completed', 'cancelled', 'no_show'])
    //               ->default('scheduled');

    // ميزة الـ Follow Ups (المراجعات)
    // نربط موعد المراجعة بالموعد الأب (الزيارة الأولى)، وإذا حُذف الموعد الأب يبقى موعد المراجعة مستقلاً (Set Null)
    //         $table->foreignId('parent_appointment_id')
    //               ->nullable()
    //               ->constrained('appointments')
    //               ->nullOnDelete();

    // نوع الموعد للتمييز البرمجي (كشفية أولى أم مراجعة) لتسهيل حساب السعر والتقارير
    //         $table->enum('type', ['initial', 'follow_up'])->default('initial');

    // تكلفة الموعد (مهم جداً لأن الـ follow_up ستكون قيمته 0.00 غالباً)
    //         $table->decimal('price', 8, 2)->default(0.00);

    // ملاحظات عامة
    //         $table->text('notes')->nullable();

    //         $table->timestamps();
    //     });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
