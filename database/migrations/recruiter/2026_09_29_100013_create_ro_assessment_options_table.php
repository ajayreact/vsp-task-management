<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_assessment_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('ro_assessment_questions')->cascadeOnDelete();
            // A, B, C ... with no fixed upper limit.
            $table->string('option_key', 5);
            $table->text('text');
            // Never sent to a recruiter before they submit.
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['question_id', 'option_key']);
            $table->index(['question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_assessment_options');
    }
};
