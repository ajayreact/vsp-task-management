<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ro_training_lesson_contents', function (Blueprint $table) {
            $table->foreignId('reviewed_by_user_id')->nullable()->after('updated_by_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });

        Schema::table('ro_training_lessons', function (Blueprint $table) {
            // Null means the lesson was never flagged, which reads as not required.
            $table->string('compliance_status', 20)->nullable()->after('is_required');
            $table->foreignId('compliance_reviewed_by_user_id')->nullable()->after('compliance_status')->constrained('users')->nullOnDelete();
            $table->timestamp('compliance_reviewed_at')->nullable()->after('compliance_reviewed_by_user_id');
            $table->text('compliance_note')->nullable()->after('compliance_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('ro_training_lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('compliance_reviewed_by_user_id');
            $table->dropColumn(['compliance_status', 'compliance_reviewed_at', 'compliance_note']);
        });

        Schema::table('ro_training_lesson_contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn(['reviewed_at', 'review_note']);
        });
    }
};
