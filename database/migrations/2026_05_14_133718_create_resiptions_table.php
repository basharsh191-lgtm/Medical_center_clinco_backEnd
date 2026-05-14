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
Schema::create('receptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();

    $table->decimal('salary', 10, 2)->nullable();
    $table->date('hiring_date')->useCurrent();

    $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
    $table->enum('shift_type', ['morning', 'evening', 'night', 'full_day']);

    $table->text('biography')->nullable();

    $table->timestamps();
    $table->softDeletes();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resiptions');
    }
};
