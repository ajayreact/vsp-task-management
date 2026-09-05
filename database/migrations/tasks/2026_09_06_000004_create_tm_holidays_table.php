<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('country', 10);
            $table->string('region', 80)->nullable();
            $table->string('name');
            $table->date('date');
            $table->unsignedSmallInteger('year');
            $table->string('holiday_type', 40)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['country', 'year', 'date'], 'tm_holidays_country_year_date_idx');
            $table->unique(['country', 'region', 'date', 'name'], 'tm_holidays_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_holidays');
    }
};
