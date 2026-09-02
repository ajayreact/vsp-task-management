<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_content_calendar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_company_id')->constrained('tm_companies')->cascadeOnDelete();
            $table->date('scheduled_date')->index();
            $table->time('scheduled_time')->nullable();
            $table->string('content_type', 20);
            $table->string('platform', 20);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tm_company_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_content_calendar_items');
    }
};
