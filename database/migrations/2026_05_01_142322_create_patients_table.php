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
    Schema::create('patients', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->enum('gender', ['male', 'female', 'other']);
        $table->string('address');
        $table->date('birth_date');
        $table->text('allergies')->nullable();
        $table->text('hereditary')->nullable();
        $table->text('chronic_diseases')->nullable();
        $table->text('blood_type');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
