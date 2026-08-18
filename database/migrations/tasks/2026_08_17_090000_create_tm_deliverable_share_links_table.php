<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_deliverable_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_deliverable_id')
                ->unique()
                ->constrained('tm_deliverables')
                ->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_deliverable_share_links');
    }
};
