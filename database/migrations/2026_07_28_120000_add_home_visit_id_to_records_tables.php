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
        foreach (['medical_records', 'prescriptions', 'lab_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'home_visit_id')) {
                    $table->foreignId('home_visit_id')->nullable()->after('appointment_id')
                        ->constrained('home_visits')->cascadeOnDelete();
                }

                $table->unsignedBigInteger('appointment_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['medical_records', 'prescriptions', 'lab_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'home_visit_id')) {
                    $table->dropForeign(['home_visit_id']);
                    $table->dropColumn('home_visit_id');
                }
            });
        }
    }
};
