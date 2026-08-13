<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_company_id')->constrained('tm_companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32)->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('planning')->index();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            // References the shared employees table, which Core owns. This is
            // one of the module's four permitted outward references.
            $table->foreignId('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('budget_hours', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('tm_project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_project_id')->constrained('tm_projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('project_role', 20)->default('member');
            $table->timestamps();

            $table->unique(['tm_project_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_project_members');
        Schema::dropIfExists('tm_projects');
    }
};
