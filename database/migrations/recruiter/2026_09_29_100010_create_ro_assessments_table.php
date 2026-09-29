<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_assessments', function (Blueprint $table) {
            $table->id();
            // Only "training_quiz" is offered today; the column leaves room for
            // recruiter and practical assessments on the same engine.
            $table->string('type', 30)->default('training_quiz');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            // The version new assignments get. No FK: versions reference assessments.
            $table->unsignedBigInteger('current_version_id')->nullable()->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_assessments');
    }
};
