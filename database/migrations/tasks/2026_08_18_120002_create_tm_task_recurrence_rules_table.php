<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_task_recurrence_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_tm_task_id')->constrained('tm_tasks')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('frequency', 20);
            $table->unsignedInteger('interval')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->unsignedInteger('occurrences_generated')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();

            $table->unique('source_tm_task_id');
        });

        Schema::table('tm_tasks', function (Blueprint $table) {
            $table->foreignId('tm_recurrence_rule_id')
                ->nullable()
                ->after('completed_at')
                ->constrained('tm_task_recurrence_rules')
                ->nullOnDelete();
            $table->unsignedInteger('recurrence_occurrence_number')->nullable()->after('tm_recurrence_rule_id');

            $table->unique(
                ['tm_recurrence_rule_id', 'recurrence_occurrence_number'],
                'tm_tasks_recurrence_rule_occurrence_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('tm_tasks', function (Blueprint $table) {
            $table->dropUnique('tm_tasks_recurrence_rule_occurrence_unique');
            $table->dropConstrainedForeignId('tm_recurrence_rule_id');
            $table->dropColumn('recurrence_occurrence_number');
        });

        Schema::dropIfExists('tm_task_recurrence_rules');
    }
};
