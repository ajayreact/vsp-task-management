<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('income_date');
            $table->string('person_name');
            $table->string('mobile_number', 20)->nullable();
            $table->string('reason');
            $table->decimal('amount', 12, 2);
            $table->string('status', 20); // received|pending|cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'income_date']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_incomes');
    }
};
