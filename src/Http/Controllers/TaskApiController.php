<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Dooz\Worklog\Services\WorklogStateMachine;

/**
 * TaskApiController - API endpoints for Quicky ↔ Worklog integration
 */
class TaskApiController extends Controller
{
    /**
     * Create a Worklog Task from a Quicky
     * POST /api/worklog/tasks/from-quicky
     * 
     * Body: {
     *   quicky_id: string (required),
     *   name: string (required),
     *   project_id: int (required),
     *   description: string (optional)
     * }
     */
    public function createFromQuicky(Request $request)
    {
        $request->validate([
            'quicky_id' => 'required|string',
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:worklog_projects,id',
            'description' => 'nullable|string',
        ]);

        // Check if task already linked to this Quicky
        $existing = DB::table('worklog_tasks')
            ->where('linked_quicky_id', $request->input('quicky_id'))
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A task is already linked to this Quicky.',
                'task_id' => $existing->id
            ], 409);
        }

        $taskId = DB::table('worklog_tasks')->insertGetId([
            'name' => $request->input('name'),
            'project_id' => $request->input('project_id'),
            'description' => $request->input('description'),
            'status' => WorklogStateMachine::NEW,
            'is_billable' => true,
            'linked_quicky_id' => $request->input('quicky_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Worklog task created successfully.',
            'task_id' => $taskId,
            'linked_quicky_id' => $request->input('quicky_id')
        ], 201);
    }

    /**
     * Check status of all tasks linked to a Quicky
     * GET /api/worklog/quicky/{quickyId}/linked-status
     * 
     * Returns signal for Quicky UI: "All linked tasks completed"
     */
    public function checkLinkedStatus($quickyId)
    {
        $tasks = DB::table('worklog_tasks')
            ->where('linked_quicky_id', $quickyId)
            ->get(['id', 'name', 'status']);

        if ($tasks->isEmpty()) {
            return response()->json([
                'linked' => false,
                'message' => 'No Worklog tasks linked to this Quicky.'
            ]);
        }

        $allCompleted = $tasks->every(fn($t) => $t->status === WorklogStateMachine::COMPLETED);
        $anyInProgress = $tasks->contains(fn($t) => $t->status === WorklogStateMachine::IN_PROGRESS);
        $anyOnHold = $tasks->contains(fn($t) => $t->status === WorklogStateMachine::ON_HOLD);

        // Calculate total effort
        $taskIds = $tasks->pluck('id')->toArray();
        $totalMinutes = DB::table('worklog_time_logs')
            ->whereIn('task_id', $taskIds)
            ->sum('duration_minutes');
        $totalHours = round($totalMinutes / 60, 1);

        return response()->json([
            'linked' => true,
            'task_count' => $tasks->count(),
            'all_completed' => $allCompleted,
            'any_in_progress' => $anyInProgress,
            'any_on_hold' => $anyOnHold,
            'total_effort_hours' => $totalHours,
            'signal' => $allCompleted ? 'All linked execution tasks completed' : null,
            'tasks' => $tasks
        ]);
    }

    /**
     * Flag linked tasks when a Quicky is cancelled/rejected
     * POST /api/worklog/quicky/{quickyId}/flag-tasks
     * 
     * Per PRD: No auto-closure, just flag for human decision
     */
    public function flagLinkedTasks(Request $request, $quickyId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255'
        ]);

        $tasks = DB::table('worklog_tasks')
            ->where('linked_quicky_id', $quickyId)
            ->whereNotIn('status', [WorklogStateMachine::COMPLETED, WorklogStateMachine::CANCELLED])
            ->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'flagged' => false,
                'message' => 'No active tasks to flag.'
            ]);
        }

        // Flag by putting on hold and adding note
        $reason = $request->input('reason', 'Linked Quicky was cancelled/rejected');
        $flaggedCount = 0;

        foreach ($tasks as $task) {
            // Only flag if we can transition to ON_HOLD
            if (WorklogStateMachine::canTransition($task->status, WorklogStateMachine::ON_HOLD)) {
                DB::table('worklog_tasks')->where('id', $task->id)->update([
                    'status' => WorklogStateMachine::ON_HOLD,
                    'updated_at' => now()
                ]);
                $flaggedCount++;
            }
        }

        return response()->json([
            'flagged' => true,
            'flagged_count' => $flaggedCount,
            'reason' => $reason,
            'message' => "Flagged {$flaggedCount} task(s) for review. Human decision required."
        ]);
    }
}
