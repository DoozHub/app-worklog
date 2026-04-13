<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class WorklogWebController extends Controller
{
    public function index()
    {
        $userId = auth()->id(); // Assuming user ID is string/UUID given older context, or int. DB table check showed user_id.

        $tasks = DB::table('worklog_tasks')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select(
                'worklog_tasks.*',
                'worklog_projects.name as project_name',
                'worklog_clients.name as client_name'
            )
            ->orderBy('worklog_tasks.created_at', 'desc')
            ->get();

        // Metrics
        $inProgressCount = DB::table('worklog_tasks')->where('status', 'in_progress')->count();
        $blockedCount = DB::table('worklog_tasks')->where('status', 'blocked')->count();

        // Logged Today
        $todayMinutes = DB::table('worklog_time_logs')
            // ->where('user_id', $userId) // Uncomment when user_id is properly populated
            ->whereDate('date', now()->toDateString())
            ->sum('duration_minutes');
        
        $todayHours = floor($todayMinutes / 60);
        $todayRemainingMinutes = $todayMinutes % 60;

        // Daily Ledger
        $ledgerEntries = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->select(
                'worklog_time_logs.*',
                'worklog_tasks.name as task_name',
                'worklog_projects.name as project_name'
            )
            ->whereDate('worklog_time_logs.date', now()->toDateString())
            ->orderBy('worklog_time_logs.created_at', 'desc')
            ->get();

        return view('dooz-worklog::index', [
            'tasks' => $tasks,
            'inProgressCount' => $inProgressCount,
            'blockedCount' => $blockedCount,
            'todayHours' => $todayHours,
            'todayRemainingMinutes' => $todayRemainingMinutes,
            'ledgerEntries' => $ledgerEntries
        ]);
    }

    public function create()
    {
        $projects = DB::table('worklog_projects')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select(
                'worklog_projects.*',
                'worklog_clients.name as client_name'
            )
            ->where('worklog_projects.status', 'active')
            ->get();

        return view('dooz-worklog::create', ['projects' => $projects]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255', // Task Name / Notes
            'project_id' => 'required|exists:worklog_projects,id',
            'duration' => 'nullable|numeric',
            'unit' => 'nullable|in:min,hr',
            'date' => 'nullable|string', // Assuming date string format
        ]);

        // 1. Create Task (or find existing if we had a selection, but here we create new for every entry as per "New Entry" flow generally implies a fresh log or task)
        // Ideally we might want to select an existing task, but for this simple UI, we'll create a task wrapper for the log.
        $taskId = DB::table('worklog_tasks')->insertGetId([
            'name' => $request->input('name') ?? 'Untitled Task',
            'project_id' => $request->input('project_id'),
            'status' => 'new',
            'linked_quicky_id' => $request->input('linked_quicky_id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create Time Log if duration is provided
        if ($request->filled('duration')) {
            $duration = $request->input('duration');
            $unit = $request->input('unit', 'min');
            
            $durationMinutes = $unit === 'hr' ? $duration * 60 : $duration;

            // Parse date "M d, Y (l)" back to Y-m-d, or use today if parsing fails/complex. 
            // Since the input is readonly "Today", we can just use today's date for now or try to parse.
            // Simplified: Use current date/provided date if strictly formatted. 
            // For now, defaulting to now() as the UI had a readonly field for today.
            $date = now()->toDateString(); 

            DB::table('worklog_time_logs')->insert([
                'task_id' => $taskId,
                'user_id' => auth()->id() ?? 1, // Fallback to 1 if auth missing (though auth middleware covers this)
                'date' => $date,
                'duration_minutes' => $durationMinutes,
                'note' => $request->input('name'), // Use task desc as note too
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('dooz.worklog.index')->with('success', 'Entry saved successfully.');
    }
}
