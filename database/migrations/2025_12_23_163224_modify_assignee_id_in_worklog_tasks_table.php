<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if assignee_id already exists and is the correct type (uuid/char(36))
        if (Schema::hasColumn('worklog_tasks', 'assignee_id')) {
            // Column exists - check if index exists (database-agnostic)
            $driver = DB::connection()->getDriverName();
            $indexExists = false;

            if ($driver === 'pgsql') {
                $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'worklog_tasks' AND indexname = 'worklog_tasks_assignee_id_index'");
                $indexExists = !empty($indexes);
            } else {
                $indexes = DB::select("SHOW INDEX FROM worklog_tasks WHERE Key_name = 'worklog_tasks_assignee_id_index'");
                $indexExists = !empty($indexes);
            }

            if ($indexExists) {
                // Already migrated correctly, skip
                return;
            }

            // Column exists but may need to be re-created as UUID
            // First, drop FK if exists using Schema (database-agnostic)
            try {
                Schema::table('worklog_tasks', function (Blueprint $table) {
                    $table->dropForeign(['assignee_id']);
                });
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }

            // Drop and recreate the column
            Schema::table('worklog_tasks', function (Blueprint $table) {
                $table->dropColumn('assignee_id');
            });
        }

        // Add the column as UUID with index
        if (!Schema::hasColumn('worklog_tasks', 'assignee_id')) {
            Schema::table('worklog_tasks', function (Blueprint $table) {
                $table->uuid('assignee_id')->nullable();
                $table->index('assignee_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worklog_tasks', function (Blueprint $table) {
            $table->dropColumn('assignee_id');
        });

        Schema::table('worklog_tasks', function (Blueprint $table) {
            $table->foreignId('assignee_id')->nullable()->constrained('users');
        });
    }
};
