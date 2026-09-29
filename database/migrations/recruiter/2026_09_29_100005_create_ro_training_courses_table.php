<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_training_courses', function (Blueprint $table) {
            $table->id();
            // A category holding courses cannot be removed; deactivate it instead.
            $table->foreignId('category_id')->constrained('ro_training_categories')->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            // The published version new assignments receive. Deliberately not a
            // foreign key: versions already reference their course.
            $table->unsignedBigInteger('current_version_id')->nullable()->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_training_courses');
    }
};
