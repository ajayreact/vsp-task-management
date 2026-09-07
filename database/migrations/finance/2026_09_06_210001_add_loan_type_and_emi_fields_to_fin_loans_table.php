<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_loans', function (Blueprint $table) {
            $table->string('loan_type', 20)->default('personal')->after('user_id');
            $table->decimal('emi_amount', 12, 2)->nullable()->after('remaining_amount');
            $table->unsignedTinyInteger('emi_due_day')->nullable()->after('emi_amount');
            $table->date('next_emi_due_date')->nullable()->after('emi_due_day');

            $table->index(['user_id', 'loan_type']);
            $table->index(['user_id', 'next_emi_due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('fin_loans', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'loan_type']);
            $table->dropIndex(['user_id', 'next_emi_due_date']);
            $table->dropColumn(['loan_type', 'emi_amount', 'emi_due_day', 'next_emi_due_date']);
        });
    }
};
