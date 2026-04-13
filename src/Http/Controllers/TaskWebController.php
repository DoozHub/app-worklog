<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Dooz\Worklog\Services\WorklogStateMachine;

class TaskWebController extends Controller
{
    public function index()
    {
        // Task Ledger - Use leftJoin to handle tasks without projects/clients
        $tasks = DB::table('worklog_tasks')
            ->leftJoin('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->leftJoin('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select(
                'worklog_tasks.*',
                'worklog_projects.name as project_name',
                'worklog_clients.name as client_name'
            )
            ->orderBy('worklog_tasks.created_at', 'desc')
            ->get();

        // Calculate logged time per task
        $tasks->transform(function ($task) {
            $minutes = DB::table('worklog_time_logs')->where('task_id', $task->id)->sum('duration_minutes');
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            $task->logged_label = ($minutes > 0) ? sprintf('%dh %02dm', $hours, $mins) : '-';
            $task->project_name = $task->project_name ?? 'No Project';
            $task->client_name = $task->client_name ?? 'No Client';
            return $task;
        });

        return view('dooz-worklog::ledger.index', ['tasks' => $tasks]);
    }

    public function show($id)
    {
        // Task Details - Use leftJoin
        $task = DB::table('worklog_tasks')
            ->leftJoin('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->leftJoin('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select(
                'worklog_tasks.*',
                'worklog_projects.name as project_name',
                'worklog_clients.name as client_name',
                'users.name as assignee_name'
            )
            ->leftJoin('users', 'worklog_tasks.assignee_id', '=', 'users.id')
            ->where('worklog_tasks.id', $id)
            ->first();

        if (!$task) abort(404);

        // Set defaults for missing relations
        $task->project_name = $task->project_name ?? 'No Project';
        $task->client_name = $task->client_name ?? 'No Client';

        $logs = DB::table('worklog_time_logs')
            ->where('task_id', $id)
            ->orderBy('date', 'desc')
            ->get();

        $totalMinutes = $logs->sum('duration_minutes');
        $hours = floor($totalMinutes / 60);
        $mins = $totalMinutes % 60;
        $totalLogged = sprintf('%dh %02dm', $hours, $mins);

        // Get available status transitions
        $availableTransitions = WorklogStateMachine::getAvailableTransitions($task->status);
        $canLogTime = WorklogStateMachine::canLogTime($task->status);

        return view('dooz-worklog::ledger.show', [
            'task' => $task,
            'logs' => $logs,
            'totalLogged' => $totalLogged,
            'availableTransitions' => $availableTransitions,
            'canLogTime' => $canLogTime
        ]);
    }

    public function create()
    {
        $projects = DB::table('worklog_projects')
            ->leftJoin('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select('worklog_projects.*', 'worklog_clients.name as client_name')
            ->where('worklog_projects.status', 'active')
            ->get();
        
        $users = \App\Models\User::all();

        return view('dooz-worklog::ledger.create', ['projects' => $projects, 'users' => $users]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:worklog_projects,id',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        DB::table('worklog_tasks')->insert([
            'name' => $request->input('name'),
            'project_id' => $request->input('project_id'),
            'description' => $request->input('description'),
            'status' => 'new',
            'is_billable' => $request->boolean('is_billable', true),
            'linked_quicky_id' => $request->input('linked_quicky_id'),
            'linked_quicky_id' => $request->input('linked_quicky_id'),
            'estimated_minutes' => $request->input('estimated_minutes'),
            'assignee_id' => $request->input('assignee_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dooz.worklog.tasks.index')->with('success', 'Task created successfully.');
    }

    public function edit($id)
    {
        $task = DB::table('worklog_tasks')->where('id', $id)->first();
        if (!$task) abort(404);

        $projects = DB::table('worklog_projects')
            ->leftJoin('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select('worklog_projects.*', 'worklog_clients.name as client_name')
            ->where('worklog_projects.status', 'active')
            ->get();

        $users = \App\Models\User::all();

        return view('dooz-worklog::ledger.edit', ['task' => $task, 'projects' => $projects, 'users' => $users]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:worklog_projects,id',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'estimated_minutes' => 'nullable|integer|min:0',
        ]);

        $task = DB::table('worklog_tasks')->where('id', $id)->first();
        if (!$task) abort(404);

        DB::table('worklog_tasks')->where('id', $id)->update([
            'name' => $request->input('name'),
            'project_id' => $request->input('project_id'),
            'description' => $request->input('description'),
            'due_date' => $request->input('due_date'),
            'is_billable' => $request->boolean('is_billable', true),
            'estimated_minutes' => $request->input('estimated_minutes'),
            'assignee_id' => $request->input('assignee_id'),
            'updated_at' => now(),
        ]);

        return redirect()->route('dooz.worklog.tasks.show', $id)->with('success', 'Task updated successfully.');
    }

    /**
     * Update task status with state machine validation
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:new,in_progress,on_hold,completed,cancelled'
        ]);

        $task = DB::table('worklog_tasks')->where('id', $id)->first();
        if (!$task) abort(404);

        $newStatus = $request->input('status');
        $currentStatus = $task->status;

        // Validate transition using state machine
        if (!WorklogStateMachine::canTransition($currentStatus, $newStatus)) {
            return redirect()->back()->withErrors([
                'status' => "Cannot transition from '{$currentStatus}' to '{$newStatus}'."
            ]);
        }

        DB::table('worklog_tasks')->where('id', $id)->update([
            'status' => $newStatus,
            'updated_at' => now()
        ]);

        $actionLabel = ucwords(str_replace('_', ' ', $newStatus));
        return redirect()->route('dooz.worklog.tasks.show', $id)
            ->with('success', "Task status changed to {$actionLabel}.");
    }
}


