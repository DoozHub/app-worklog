<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Dooz\Worklog\Services\WorklogStateMachine;

class TimeLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->leftJoin('users', 'worklog_time_logs.user_id', '=', 'users.id')
            ->select(
                'worklog_time_logs.*',
                'worklog_tasks.name as task_name',
                'worklog_tasks.is_billable',
                'worklog_projects.name as project_name',
                'worklog_clients.name as client_name',
                'users.name as user_name'
            );

        // Filter by task
        if ($request->has('task_id')) {
            $query->where('worklog_time_logs.task_id', $request->input('task_id'));
        }

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('worklog_tasks.project_id', $request->input('project_id'));
        }

        // Filter by client
        if ($request->has('client_id')) {
            $query->where('worklog_projects.client_id', $request->input('client_id'));
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('worklog_time_logs.date', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('worklog_time_logs.date', '<=', $request->input('end_date'));
        }

        return response()->json($query->orderBy('worklog_time_logs.date', 'desc')->get());
    }

    public function show($id)
    {
        $log = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->select(
                'worklog_time_logs.*',
                'worklog_tasks.name as task_name',
                'worklog_projects.name as project_name'
            )
            ->where('worklog_time_logs.id', $id)
            ->first();

        if (!$log) {
            return response()->json(['error' => 'Time log not found'], 404);
        }

        return response()->json($log);
    }

    public function store(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:worklog_tasks,id',
            'duration_minutes' => 'required|integer|min:1',
            'date' => 'required|date',
            'work_type' => 'required|string',
        ]);

        // Check task status - can't log time on completed/cancelled tasks
        $task = DB::table('worklog_tasks')->where('id', $request->input('task_id'))->first();
        if ($task && !WorklogStateMachine::canLogTime($task->status)) {
            return response()->json([
                'error' => 'Cannot log time on ' . $task->status . ' tasks',
                'task_status' => $task->status
            ], 400);
        }

        $id = DB::table('worklog_time_logs')->insertGetId([
            'task_id' => $request->input('task_id'),
            'user_id' => auth()->id() ?? 1,
            'date' => $request->input('date'),
            'duration_minutes' => $request->input('duration_minutes'),
            'work_type' => $request->input('work_type'),
            'note' => $request->input('note'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['id' => $id, 'message' => 'Time log created'], 201);
    }

    public function update(Request $request, $id)
    {
        $log = DB::table('worklog_time_logs')->where('id', $id)->first();
        if (!$log) {
            return response()->json(['error' => 'Time log not found'], 404);
        }

        // Check task status
        $task = DB::table('worklog_tasks')->where('id', $log->task_id)->first();
        if ($task && in_array($task->status, ['completed', 'cancelled'])) {
            return response()->json([
                'error' => 'Cannot update time log for ' . $task->status . ' tasks'
            ], 400);
        }

        $updateData = [];
        if ($request->has('duration_minutes')) $updateData['duration_minutes'] = $request->input('duration_minutes');
        if ($request->has('date')) $updateData['date'] = $request->input('date');
        if ($request->has('work_type')) $updateData['work_type'] = $request->input('work_type');
        if ($request->has('note')) $updateData['note'] = $request->input('note');
        $updateData['updated_at'] = now();

        DB::table('worklog_time_logs')->where('id', $id)->update($updateData);

        return response()->json(['message' => 'Time log updated']);
    }

    public function destroy($id)
    {
        $log = DB::table('worklog_time_logs')->where('id', $id)->first();
        if (!$log) {
            return response()->json(['error' => 'Time log not found'], 404);
        }

        // Check task status
        $task = DB::table('worklog_tasks')->where('id', $log->task_id)->first();
        if ($task && in_array($task->status, ['completed', 'cancelled'])) {
            return response()->json([
                'error' => 'Cannot delete time log for ' . $task->status . ' tasks'
            ], 400);
        }

        DB::table('worklog_time_logs')->where('id', $id)->delete();
        return response()->json(['message' => 'Time log deleted']);
    }

    /**
     * Get unbilled time logs for a client
     */
    public function unbilledByClient($clientId)
    {
        $logs = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->where('worklog_projects.client_id', $clientId)
            ->where('worklog_tasks.is_billable', true)
            ->whereNull('worklog_time_logs.invoice_id')
            ->select(
                'worklog_time_logs.*',
                'worklog_tasks.name as task_name',
                'worklog_projects.name as project_name'
            )
            ->orderBy('worklog_time_logs.date', 'desc')
            ->get();

        return response()->json($logs);
    }
}
