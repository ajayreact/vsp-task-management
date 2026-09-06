<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('loan_date');
            $table->string('lender_name');
            $table->string('mobile_number', 20)->nullable();
            $table->string('reason');
            $table->decimal('loan_amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->string('status', 20); // active|partially_paid|paid|overdue|cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'loan_date']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_loans');
    }
};
