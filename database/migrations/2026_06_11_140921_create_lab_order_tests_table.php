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
        Schema::create('lab_order_tests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
        $table->string('test_name');
        $table->text('result_notes')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_order_tests');
    }
};
