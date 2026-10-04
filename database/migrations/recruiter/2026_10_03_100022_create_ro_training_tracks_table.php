<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ro_training_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        $now = now();

        DB::table('ro_training_tracks')->insert([
            [
                'name' => 'OPT Recruiter',
                'slug' => 'opt-recruiter',
                'description' => 'Training for recruiters who place OPT and STEM OPT candidates.',
                'status' => 'active',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Bench Sales Recruiter',
                'slug' => 'bench-sales-recruiter',
                'description' => 'Training for bench sales recruiters.',
                'status' => 'active',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ro_training_tracks');
    }
};
