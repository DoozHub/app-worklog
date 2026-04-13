<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('worklog_clients')) {
            Schema::create('worklog_clients', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('billing_type')->default('hourly'); // hourly, fixed, internal
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('worklog_projects')) {
            Schema::create('worklog_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained('worklog_clients');
                $table->string('name');
                $table->string('status')->default('active'); // active, paused, closed
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('worklog_tasks')) {
            Schema::create('worklog_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('worklog_projects');
                $table->string('name');
                $table->string('status')->default('new'); // new, in_progress, on_hold, completed
                $table->boolean('is_billable')->default(true);
                $table->string('linked_quicky_id')->nullable();
                $table->integer('estimated_minutes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('worklog_time_logs')) {
            Schema::create('worklog_time_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('worklog_tasks');
                $table->uuid('user_id')->index(); // User references stored without FK constraints (users are in core database)
                $table->date('date');
                $table->integer('duration_minutes');
                $table->string('work_type')->default('dev'); // dev, qa, review
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('worklog_time_logs');
        Schema::dropIfExists('worklog_tasks');
        Schema::dropIfExists('worklog_projects');
        Schema::dropIfExists('worklog_clients');
    }
};
