<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->string('category', 40);
            $table->unsignedTinyInteger('due_day');
            $table->boolean('active')->default(true);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'active']);
            $table->index(['user_id', 'due_day']);
        });

        Schema::table('fin_expenses', function (Blueprint $table) {
            $table->foreign('recurring_template_id')
                ->references('id')
                ->on('fin_recurring_expenses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fin_expenses', function (Blueprint $table) {
            $table->dropForeign(['recurring_template_id']);
        });

        Schema::dropIfExists('fin_recurring_expenses');
    }
};
