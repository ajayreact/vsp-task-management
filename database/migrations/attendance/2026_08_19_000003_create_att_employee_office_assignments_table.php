<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('att_employee_office_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('office_location_id')->constrained('att_office_locations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('att_employee_office_assignments');
    }
};
