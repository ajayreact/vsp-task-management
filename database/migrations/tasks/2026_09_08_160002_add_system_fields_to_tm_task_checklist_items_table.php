<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tm_task_checklist_items', function (Blueprint $table) {
            $table->string('source')->default('custom')->after('title');
            $table->string('template_key')->nullable()->after('source');
            $table->string('checklist_group')->nullable()->after('template_key');
            $table->boolean('is_mandatory')->default(false)->after('checklist_group');

            $table->unique(['tm_task_id', 'template_key'], 'tm_task_checklist_items_task_template_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tm_task_checklist_items', function (Blueprint $table) {
            $table->dropUnique('tm_task_checklist_items_task_template_unique');
            $table->dropColumn(['source', 'template_key', 'checklist_group', 'is_mandatory']);
        });
    }
};
