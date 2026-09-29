<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_training_course_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('ro_training_courses')->cascadeOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->string('status', 20)->default('draft');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'version_number']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_training_course_versions');
    }
};
