<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use phpDocumentor\Reflection\Types\Nullable;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('patients', function (Blueprint $table) {
        $table->id();
        $table->uuid('qr_token')->unique();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->enum('gender', ['male', 'female', 'other']);
        $table->string('address');
        $table->date('birth_date');
        $table->text('allergies')->nullable();
        $table->text('hereditary')->nullable();
        $table->text('chronic_diseases')->nullable();
        $table->text('blood_type')->nullable();
        $table->integer('taller')->nullable();
        $table->integer('weight')->nullable();
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
