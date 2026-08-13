<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            // The login lives on users; this is the person behind it. Every
            // employee has an account — the admin screen creates the two
            // together — so the profile goes when the account does.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code', 32)->unique();
            $table->string('designation')->nullable();
            $table->foreignId('reporting_to_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('phone', 32)->nullable();
            $table->date('joined_on')->nullable();
            $table->date('exited_on')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_employee_id']);
        });

        Schema::dropIfExists('employees');
    }
};
