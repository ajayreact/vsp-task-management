<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tm_content_calendar_item_platforms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tm_content_calendar_item_id');
            $table->string('platform', 20);
            $table->timestamps();

            $table->foreign('tm_content_calendar_item_id', 'tm_cc_item_platforms_item_fk')
                ->references('id')
                ->on('tm_content_calendar_items')
                ->cascadeOnDelete();

            $table->unique(['tm_content_calendar_item_id', 'platform'], 'tm_cc_item_platforms_unique');
            $table->index('platform', 'tm_cc_item_platforms_platform_idx');
        });

        if (Schema::hasColumn('tm_content_calendar_items', 'platform')) {
            DB::table('tm_content_calendar_items')
                ->whereNotNull('platform')
                ->where('platform', '!=', '')
                ->orderBy('id')
                ->chunkById(200, function ($items): void {
                    $rows = [];
                    $now = now();

                    foreach ($items as $item) {
                        $rows[] = [
                            'tm_content_calendar_item_id' => $item->id,
                            'platform' => $item->platform,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($rows !== []) {
                        DB::table('tm_content_calendar_item_platforms')->insertOrIgnore($rows);
                    }
                });

            Schema::table('tm_content_calendar_items', function (Blueprint $table) {
                $table->dropColumn('platform');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tm_content_calendar_items', function (Blueprint $table) {
            if (! Schema::hasColumn('tm_content_calendar_items', 'platform')) {
                $table->string('platform', 20)->nullable()->after('content_type');
            }
        });

        $pairs = DB::table('tm_content_calendar_item_platforms')
            ->select('tm_content_calendar_item_id', DB::raw('MIN(platform) as platform'))
            ->groupBy('tm_content_calendar_item_id')
            ->get();

        foreach ($pairs as $pair) {
            DB::table('tm_content_calendar_items')
                ->where('id', $pair->tm_content_calendar_item_id)
                ->update(['platform' => $pair->platform]);
        }

        Schema::dropIfExists('tm_content_calendar_item_platforms');
    }
};
