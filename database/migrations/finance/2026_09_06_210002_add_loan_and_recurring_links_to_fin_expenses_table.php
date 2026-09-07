<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_expenses', function (Blueprint $table) {
            $table->foreignId('fin_loan_id')->nullable()->after('user_id')->constrained('fin_loans')->nullOnDelete();
            $table->foreignId('fin_loan_payment_id')->nullable()->after('fin_loan_id')->constrained('fin_loan_payments')->nullOnDelete();
            $table->foreignId('recurring_template_id')->nullable()->after('fin_loan_payment_id');
            $table->foreignId('converted_to_fin_loan_id')->nullable()->after('notes')->constrained('fin_loans')->nullOnDelete();
            $table->boolean('excluded_as_liability')->default(false)->after('converted_to_fin_loan_id');

            $table->index(['user_id', 'fin_loan_id']);
            $table->index(['user_id', 'recurring_template_id']);
            $table->index(['user_id', 'excluded_as_liability']);
        });
    }

    public function down(): void
    {
        Schema::table('fin_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fin_loan_id');
            $table->dropConstrainedForeignId('fin_loan_payment_id');
            $table->dropConstrainedForeignId('converted_to_fin_loan_id');
            $table->dropColumn(['recurring_template_id', 'excluded_as_liability']);
        });
    }
};
