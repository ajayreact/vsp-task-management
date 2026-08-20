<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tm_deliverable_share_links', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('short_code');
            $table->timestamp('revoked_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('tm_deliverable_share_links', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'revoked_at']);
        });
    }
};
