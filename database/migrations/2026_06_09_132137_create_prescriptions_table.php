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
Schema::create('prescriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('appointment_id')->nullable()->constrained('appointments')->cascadeOnDelete();
    $table->foreignId('home_visit_id')->nullable()->constrained('home_visits')->cascadeOnDelete();
    $table->text('instructions')->nullable()->comment('إرشادات استخدام الوصفة');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
