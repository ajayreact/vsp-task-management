<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_task_id')->constrained('tm_tasks')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('submitted_by_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('status', 30)->default('submitted')->index();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['tm_task_id', 'version']);
        });

        Schema::create('tm_deliverable_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_deliverable_id')->constrained('tm_deliverables')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('round');
            $table->string('decision', 20);
            $table->text('comments')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(['tm_deliverable_id', 'round']);
        });

        Schema::create('tm_review_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_deliverable_review_id')->constrained('tm_deliverable_reviews')->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('page')->nullable();
            $table->decimal('x', 8, 4)->nullable();
            $table->decimal('y', 8, 4)->nullable();
            $table->text('body');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_review_annotations');
        Schema::dropIfExists('tm_deliverable_reviews');
        Schema::dropIfExists('tm_deliverables');
    }
};
