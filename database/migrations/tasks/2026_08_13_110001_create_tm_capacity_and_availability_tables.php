<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_employee_capacity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('weekly_hours', 6, 2);
            $table->json('working_days');
            $table->date('effective_from');
            $table->timestamps();

            $table->unique(['employee_id', 'effective_from']);
        });

        Schema::create('tm_employee_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20);
            $table->decimal('capacity_hours', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });

        Schema::create('tm_workload_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('assigned_hours', 6, 2);
            $table->decimal('available_hours', 6, 2);
            $table->decimal('utilisation_pct', 6, 2);
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_workload_snapshots');
        Schema::dropIfExists('tm_employee_availability');
        Schema::dropIfExists('tm_employee_capacity');
    }
};
