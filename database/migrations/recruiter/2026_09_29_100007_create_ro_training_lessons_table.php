<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_training_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_version_id')->constrained('ro_training_course_versions')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('content_type', 30)->default('text');
            // Plain text, rendered as text. Never HTML.
            $table->longText('body')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('external_url', 2048)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_version_id', 'slug']);
            $table->index(['course_version_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_training_lessons');
    }
};
