<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A work client. Deliberately unrelated to `crm_clients`: the same business may
 * appear in both, but the two are managed by different teams for different
 * purposes and must not share a lifecycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 32)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->string('primary_contact_name')->nullable();
            $table->string('primary_contact_email')->nullable();
            $table->string('primary_contact_phone', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_companies');
    }
};
