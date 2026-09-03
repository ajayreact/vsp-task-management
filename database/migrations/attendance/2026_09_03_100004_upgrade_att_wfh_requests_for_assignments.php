<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('att_wfh_requests', 'type')) {
            Schema::table('att_wfh_requests', function (Blueprint $table) {
                $table->string('type', 16)->default('request')->after('employee_id');
            });
        }

        if (! Schema::hasColumn('att_wfh_requests', 'start_date')) {
            Schema::table('att_wfh_requests', function (Blueprint $table) {
                $table->date('start_date')->nullable()->after('type');
            });
        }

        if (! Schema::hasColumn('att_wfh_requests', 'end_date')) {
            Schema::table('att_wfh_requests', function (Blueprint $table) {
                $table->date('end_date')->nullable()->after('start_date');
            });
        }

        if (! Schema::hasColumn('att_wfh_requests', 'notes')) {
            Schema::table('att_wfh_requests', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('reason');
            });
        }

        if (! Schema::hasColumn('att_wfh_requests', 'requested_by_user_id')) {
            Schema::table('att_wfh_requests', function (Blueprint $table) {
                $table->foreignId('requested_by_user_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('att_wfh_requests', 'assigned_by_user_id')) {
            Schema::table('att_wfh_requests', function (Blueprint $table) {
                $table->foreignId('assigned_by_user_id')->nullable()->after('requested_by_user_id')->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('att_wfh_requests', 'date')) {
            DB::table('att_wfh_requests')->update([
                'start_date' => DB::raw('date'),
                'end_date' => DB::raw('date'),
            ]);
        }

        if (Schema::hasColumn('att_wfh_requests', 'date')) {
            if (! $this->indexExists('att_wfh_requests', 'att_wfh_requests_employee_id_idx')) {
                Schema::table('att_wfh_requests', function (Blueprint $table) {
                    $table->index('employee_id', 'att_wfh_requests_employee_id_idx');
                });
            }

            Schema::table('att_wfh_requests', function (Blueprint $table) {
                $table->dropUnique(['employee_id', 'date']);
                $table->dropColumn('date');
            });
        }

        if (! $this->indexExists('att_wfh_requests', 'att_wfh_requests_employee_range_idx')) {
            Schema::table('att_wfh_requests', function (Blueprint $table) {
                $table->index(['employee_id', 'start_date', 'end_date'], 'att_wfh_requests_employee_range_idx');
            });
        }

        if (! $this->indexExists('att_wfh_requests', 'att_wfh_requests_type_status_idx')) {
            Schema::table('att_wfh_requests', function (Blueprint $table) {
                $table->index(['type', 'status'], 'att_wfh_requests_type_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('att_wfh_requests', function (Blueprint $table) {
            $table->date('date')->nullable()->after('employee_id');
        });

        DB::table('att_wfh_requests')->update([
            'date' => DB::raw('start_date'),
        ]);

        Schema::table('att_wfh_requests', function (Blueprint $table) {
            if ($this->indexExists('att_wfh_requests', 'att_wfh_requests_employee_range_idx')) {
                $table->dropIndex('att_wfh_requests_employee_range_idx');
            }

            if ($this->indexExists('att_wfh_requests', 'att_wfh_requests_type_status_idx')) {
                $table->dropIndex('att_wfh_requests_type_status_idx');
            }

            if ($this->indexExists('att_wfh_requests', 'att_wfh_requests_employee_id_idx')) {
                $table->dropIndex('att_wfh_requests_employee_id_idx');
            }

            $table->dropForeign(['requested_by_user_id']);
            $table->dropForeign(['assigned_by_user_id']);
            $table->dropColumn([
                'type',
                'start_date',
                'end_date',
                'notes',
                'requested_by_user_id',
                'assigned_by_user_id',
            ]);
            $table->unique(['employee_id', 'date']);
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $index],
        );

        return $result !== [];
    }
};
