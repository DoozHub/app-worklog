<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimeLogWebController extends Controller
{
    public function index(Request $request)
    {
        // "History" View (Image 3)
        $query = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select(
                'worklog_time_logs.*',
                'worklog_tasks.name as task_name',
                'worklog_tasks.is_billable',
                'worklog_projects.name as project_name',
                'worklog_clients.name as client_name'
            );

        if ($request->filled('start_date')) {
            $query->whereDate('worklog_time_logs.date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('worklog_time_logs.date', '<=', $request->input('end_date'));
        }
        if ($request->filled('client_id')) {
            $query->where('worklog_projects.client_id', $request->input('client_id'));
        }
        if ($request->filled('project_id')) {
            $query->where('worklog_projects.id', $request->input('project_id'));
        }

        $logs = $query->orderBy('worklog_time_logs.date', 'desc')->get();

        // Metrics for History Header
        $totalMinutes = $logs->sum('duration_minutes');
        $totalHours = round($totalMinutes / 60, 1);
        $billableMinutes = $logs->where('is_billable', true)->sum('duration_minutes');
        $billableHours = round($billableMinutes / 60, 1);
        $billableRatio = $totalMinutes > 0 ? round(($billableMinutes / $totalMinutes) * 100) : 0;
        $activeProjectsCount = $logs->unique('project_name')->count();

        $clients = DB::table('worklog_clients')->where('is_active', true)->get();
        $projects = DB::table('worklog_projects')->where('status', 'active')->get();

        return view('dooz-worklog::time_logs.index', [
            'logs' => $logs,
            'clients' => $clients,
            'projects' => $projects,
            'totalHours' => $totalHours,
            'billableHours' => $billableHours,
            'billableRatio' => $billableRatio,
            'activeProjectsCount' => $activeProjectsCount
        ]);
    }

    public function create()
    {
        // "Today" View (Image 2) or standard create
        // We will make the create route serve as the "Today/Daily View" which has the log input at top
        $today = Carbon::today();
        
        $logs = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->select(
                'worklog_time_logs.*',
                'worklog_tasks.name as task_name',
                'worklog_projects.name as project_name'
            )
            ->whereDate('worklog_time_logs.date', $today)
            ->orderBy('worklog_time_logs.created_at', 'desc')
            ->get();

        $totalMinutes = $logs->sum('duration_minutes');
        $totalHours = floor($totalMinutes / 60);
        $totalMins = $totalMinutes % 60;

        $tasks = DB::table('worklog_tasks')->whereIn('status', ['new', 'in_progress'])->get();

        return view('dooz-worklog::time_logs.create', [
            'logs' => $logs,
            'today' => $today,
            'totalFormatted' => sprintf('%dh %02dm', $totalHours, $totalMins),
            'tasks' => $tasks
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:worklog_tasks,id',
            'hours' => 'nullable|integer|min:0|max:24',
            'minutes' => 'nullable|integer|min:0|max:59',
            'note' => 'nullable|string'
        ]);

        // Calculate total minutes from hours and minutes inputs
        $hours = (int) $request->input('hours', 0);
        $minutes = (int) $request->input('minutes', 0);
        $totalMinutes = ($hours * 60) + $minutes;

        if ($totalMinutes <= 0) {
            return redirect()->back()->withErrors(['duration' => 'Please enter a valid duration.']);
        }
        
        DB::table('worklog_time_logs')->insert([
            'task_id' => $request->input('task_id'),
            'user_id' => auth()->id() ?? 1,
            'date' => now()->toDateString(),
            'duration_minutes' => $totalMinutes,
            'note' => $request->input('note'),
            'work_type' => 'dev',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dooz.worklog.time-logs.create')->with('success', 'Time logged successfully.');
    }
}
