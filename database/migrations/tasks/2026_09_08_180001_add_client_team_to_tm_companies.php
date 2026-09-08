<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tm_companies', function (Blueprint $table) {
            $table->foreignId('primary_responsible_employee_id')
                ->nullable()
                ->after('status')
                ->constrained('employees')
                ->nullOnDelete();
        });

        Schema::create('tm_company_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_company_id')->constrained('tm_companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tm_company_id', 'employee_id']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_company_members');

        Schema::table('tm_companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_responsible_employee_id');
        });
    }
};
