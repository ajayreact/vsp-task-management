<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_assessment_answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->constrained('ro_assessment_answers')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('ro_assessment_options')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['answer_id', 'option_id']);
            $table->index('option_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_assessment_answer_options');
    }
};
