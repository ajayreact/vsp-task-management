<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ro_training_courses', function (Blueprint $table) {
            // Null until a course is deliberately placed in a track.
            $table->foreignId('training_track_id')->nullable()->after('category_id')
                ->constrained('ro_training_tracks')->restrictOnDelete();
            $table->index(['training_track_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('ro_training_courses', function (Blueprint $table) {
            $table->dropIndex(['training_track_id', 'status']);
            $table->dropConstrainedForeignId('training_track_id');
        });
    }
};
