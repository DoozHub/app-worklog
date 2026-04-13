<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorklogController extends Controller
{
    /**
     * Dashboard statistics for API
     */
    public function dashboard()
    {
        $userId = auth()->id() ?? 1;
        $today = Carbon::today()->toDateString();
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        // Count tasks by status
        $inProgressCount = DB::table('worklog_tasks')
            ->where('status', 'in_progress')
            ->count();

        $onHoldCount = DB::table('worklog_tasks')
            ->where('status', 'on_hold')
            ->count();

        // Today's logged time
        $todayMinutes = DB::table('worklog_time_logs')
            ->where('date', $today)
            ->where('user_id', $userId)
            ->sum('duration_minutes');

        // This week's logged time
        $weekMinutes = DB::table('worklog_time_logs')
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->where('user_id', $userId)
            ->sum('duration_minutes');

        // Recent time logs
        $recentLogs = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->where('worklog_time_logs.user_id', $userId)
            ->select(
                'worklog_time_logs.*',
                'worklog_tasks.name as task_name',
                'worklog_projects.name as project_name'
            )
            ->orderBy('worklog_time_logs.created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'in_progress_count' => $inProgressCount,
            'on_hold_count' => $onHoldCount,
            'today_minutes' => (int) $todayMinutes,
            'today_hours' => round($todayMinutes / 60, 1),
            'week_minutes' => (int) $weekMinutes,
            'week_hours' => round($weekMinutes / 60, 1),
            'recent_logs' => $recentLogs
        ]);
    }
}
