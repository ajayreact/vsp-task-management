<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ro_training_lessons', function (Blueprint $table) {
            // Consecutive lessons with the same module form one module on the
            // learner page. Null groups the lesson under the course itself.
            $table->string('module', 150)->nullable()->after('course_version_id');
        });
    }

    public function down(): void
    {
        Schema::table('ro_training_lessons', function (Blueprint $table) {
            $table->dropColumn('module');
        });
    }
};
