<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fin_loan_payments', function (Blueprint $table) {
            $table->string('payment_type', 20)->default('partial')->after('amount');
            $table->decimal('remaining_balance_after', 12, 2)->nullable()->after('payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('fin_loan_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'remaining_balance_after']);
        });
    }
};
