<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_assessment_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('ro_assessments')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            // draft (editable) -> published (frozen) -> archived (frozen).
            $table->string('status', 20)->default('draft');
            $table->text('instructions')->nullable();
            $table->unsignedTinyInteger('passing_percentage')->default(70);
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->unsignedTinyInteger('max_attempts')->default(2);
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_options')->default(false);
            $table->boolean('show_result')->default(true);
            $table->boolean('allow_review')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assessment_id', 'version_number']);
            $table->index(['assessment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_assessment_versions');
    }
};
