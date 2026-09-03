<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_company_phone_numbers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tm_company_id')->constrained('tm_companies')->cascadeOnDelete();
            $table->string('label', 50)->nullable();
            $table->string('phone', 32);
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tm_company_id', 'sort_order']);
        });

        DB::table('tm_companies')
            ->whereNotNull('primary_contact_phone')
            ->where('primary_contact_phone', '!=', '')
            ->orderBy('id')
            ->get()
            ->each(function (object $company): void {
                DB::table('tm_company_phone_numbers')->insert([
                    'tm_company_id' => $company->id,
                    'label' => 'Primary',
                    'phone' => $company->primary_contact_phone,
                    'is_primary' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_company_phone_numbers');
    }
};
