<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Dooz\Worklog\Services\WorklogStateMachine;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('worklog_tasks')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->leftJoin('users', 'worklog_tasks.assignee_id', '=', 'users.id')
            ->select(
                'worklog_tasks.*',
                'worklog_projects.name as project_name',
                'worklog_clients.name as client_name',
                'worklog_clients.id as client_id',
                'users.name as assignee_name'
            );

        // Filter by status
        if ($request->has('status')) {
            $query->where('worklog_tasks.status', $request->input('status'));
        }

        // Filter for active tasks
        if ($request->input('active') === 'true') {
            $query->whereIn('worklog_tasks.status', ['new', 'in_progress', 'on_hold']);
        }

        $tasks = $query->orderBy('worklog_tasks.updated_at', 'desc')->get();

        // Calculate logged time for each task
        $tasks = $tasks->map(function ($task) {
            $minutes = DB::table('worklog_time_logs')->where('task_id', $task->id)->sum('duration_minutes');
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            $task->total_logged_minutes = (int) $minutes;
            $task->logged_formatted = "{$hours}h {$mins}m";
            return $task;
        });

        return response()->json($tasks);
    }

    public function show($id)
    {
        $task = DB::table('worklog_tasks')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->leftJoin('users', 'worklog_tasks.assignee_id', '=', 'users.id')
            ->select(
                'worklog_tasks.*',
                'worklog_projects.name as project_name',
                'worklog_clients.name as client_name',
                'users.name as assignee_name'
            )
            ->where('worklog_tasks.id', $id)
            ->first();

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        // Get logged time
        $minutes = DB::table('worklog_time_logs')->where('task_id', $task->id)->sum('duration_minutes');
        $task->total_logged_minutes = (int) $minutes;
        $task->logged_formatted = floor($minutes / 60) . 'h ' . ($minutes % 60) . 'm';

        // Get time logs
        $task->time_logs = DB::table('worklog_time_logs')
            ->leftJoin('users', 'worklog_time_logs.user_id', '=', 'users.id')
            ->where('worklog_time_logs.task_id', $id)
            ->select('worklog_time_logs.*', 'users.name as user_name')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($task);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:worklog_projects,id',
        ]);

        $id = DB::table('worklog_tasks')->insertGetId([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'project_id' => $request->input('project_id'),
            'status' => 'new',
            'is_billable' => $request->input('is_billable', true),
            'linked_quicky_id' => $request->input('linked_quicky_id'),
            'assignee_id' => $request->input('assignee_id'),
            'estimated_minutes' => $request->input('estimated_minutes'),
            'due_date' => $request->input('due_date'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['id' => $id, 'message' => 'Task created'], 201);
    }

    public function update(Request $request, $id)
    {
        $task = DB::table('worklog_tasks')->where('id', $id)->first();
        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        // Can't update completed or cancelled tasks
        if (in_array($task->status, ['completed', 'cancelled'])) {
            return response()->json(['error' => 'Cannot update completed or cancelled tasks'], 400);
        }

        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $request->input('name');
        if ($request->has('description')) $updateData['description'] = $request->input('description');
        if ($request->has('is_billable')) $updateData['is_billable'] = $request->input('is_billable');
        if ($request->has('estimated_minutes')) $updateData['estimated_minutes'] = $request->input('estimated_minutes');
        if ($request->has('due_date')) $updateData['due_date'] = $request->input('due_date');
        if ($request->has('assignee_id')) $updateData['assignee_id'] = $request->input('assignee_id');
        $updateData['updated_at'] = now();

        DB::table('worklog_tasks')->where('id', $id)->update($updateData);

        return response()->json(['message' => 'Task updated']);
    }

    public function destroy($id)
    {
        $task = DB::table('worklog_tasks')->where('id', $id)->first();
        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        // Check if task has time logs
        $logCount = DB::table('worklog_time_logs')->where('task_id', $id)->count();
        if ($logCount > 0) {
            return response()->json([
                'error' => 'Cannot delete task with time logs',
                'log_count' => $logCount
            ], 400);
        }

        DB::table('worklog_tasks')->where('id', $id)->delete();
        return response()->json(['message' => 'Task deleted']);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:new,in_progress,on_hold,completed,cancelled'
        ]);

        $task = DB::table('worklog_tasks')->where('id', $id)->first();
        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $newStatus = $request->input('status');

        // Validate transition using state machine
        if (!WorklogStateMachine::canTransition($task->status, $newStatus)) {
            return response()->json([
                'error' => 'Invalid status transition',
                'from' => $task->status,
                'to' => $newStatus,
                'allowed' => WorklogStateMachine::getAvailableTransitions($task->status)
            ], 400);
        }

        DB::table('worklog_tasks')->where('id', $id)->update([
            'status' => $newStatus,
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Status updated',
            'from' => $task->status,
            'to' => $newStatus
        ]);
    }
}
