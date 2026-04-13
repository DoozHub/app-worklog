<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Force change assignee_id to char(36) for UUID support
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE worklog_tasks ALTER COLUMN assignee_id TYPE CHAR(36)');
        } else {
            DB::statement('ALTER TABLE worklog_tasks MODIFY assignee_id CHAR(36) NULL');
        }
    }

    public function down(): void
    {
        // Revert to bigint (will lose UUID data)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE worklog_tasks ALTER COLUMN assignee_id TYPE BIGINT USING assignee_id::BIGINT');
        } else {
            DB::statement('ALTER TABLE worklog_tasks MODIFY assignee_id BIGINT UNSIGNED NULL');
        }
    }
};
