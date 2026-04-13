<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ReportController - API controller for Report operations
 */
class ReportController extends Controller
{
    /**
     * Get time spent per client
     */
    public function byClient(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $data = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->whereBetween('worklog_time_logs.date', [$startDate, $endDate])
            ->groupBy('worklog_clients.id', 'worklog_clients.name')
            ->selectRaw('worklog_clients.id, worklog_clients.name as client_name, SUM(duration_minutes) as total_minutes')
            ->orderByDesc('total_minutes')
            ->get();

        return response()->json($data->map(function ($row) {
            $row->total_hours = round($row->total_minutes / 60, 1);
            return $row;
        }));
    }

    /**
     * Get time spent per project
     */
    public function byProject(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $clientId = $request->input('client_id');

        $query = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->whereBetween('worklog_time_logs.date', [$startDate, $endDate]);

        if ($clientId) {
            $query->where('worklog_projects.client_id', $clientId);
        }

        $data = $query->groupBy('worklog_projects.id', 'worklog_projects.name', 'worklog_clients.name')
            ->selectRaw('worklog_projects.id, worklog_projects.name as project_name, worklog_clients.name as client_name, SUM(duration_minutes) as total_minutes')
            ->orderByDesc('total_minutes')
            ->get();

        return response()->json($data->map(function ($row) {
            $row->total_hours = round($row->total_minutes / 60, 1);
            return $row;
        }));
    }

    /**
     * Get billable vs non-billable summary
     */
    public function billableSummary(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $billable = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->where('worklog_tasks.is_billable', true)
            ->whereBetween('worklog_time_logs.date', [$startDate, $endDate])
            ->sum('duration_minutes');

        $nonBillable = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->where('worklog_tasks.is_billable', false)
            ->whereBetween('worklog_time_logs.date', [$startDate, $endDate])
            ->sum('duration_minutes');

        return response()->json([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'billable_minutes' => (int) $billable,
            'billable_hours' => round($billable / 60, 1),
            'non_billable_minutes' => (int) $nonBillable,
            'non_billable_hours' => round($nonBillable / 60, 1),
            'total_minutes' => (int) ($billable + $nonBillable),
            'total_hours' => round(($billable + $nonBillable) / 60, 1),
            'billable_percentage' => ($billable + $nonBillable) > 0 
                ? round(($billable / ($billable + $nonBillable)) * 100, 1) 
                : 0
        ]);
    }

    /**
     * Get weekly summary
     */
    public function weekly(Request $request)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $dailyTotals = DB::table('worklog_time_logs')
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->where('user_id', auth()->id() ?? 1)
            ->groupBy('date')
            ->selectRaw('date, SUM(duration_minutes) as total_minutes')
            ->orderBy('date')
            ->get();

        $weekTotal = $dailyTotals->sum('total_minutes');

        return response()->json([
            'week_start' => $startOfWeek->toDateString(),
            'week_end' => $endOfWeek->toDateString(),
            'daily' => $dailyTotals->map(function ($day) {
                $day->total_hours = round($day->total_minutes / 60, 1);
                $day->day_name = Carbon::parse($day->date)->format('l');
                return $day;
            }),
            'total_minutes' => $weekTotal,
            'total_hours' => round($weekTotal / 60, 1),
            'target_hours' => 40,
            'progress_percentage' => round(($weekTotal / 60 / 40) * 100, 1)
        ]);
    }

    /**
     * Export as CSV
     */
    public function exportCsv(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', Carbon::now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', Carbon::now()));
        $clientId = $request->input('client_id');

        $query = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->leftJoin('users', 'worklog_time_logs.user_id', '=', 'users.id')
            ->whereBetween('worklog_time_logs.date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($clientId) {
            $query->where('worklog_projects.client_id', $clientId);
        }

        $logs = $query->select([
            'worklog_time_logs.date',
            'worklog_time_logs.duration_minutes',
            'worklog_time_logs.work_type',
            'worklog_time_logs.note',
            'worklog_tasks.name as task_name',
            'worklog_tasks.is_billable',
            'worklog_projects.name as project_name',
            'worklog_clients.name as client_name',
            'users.name as user_name'
        ])->orderBy('worklog_time_logs.date', 'desc')->get();

        $csv = "Date,Duration (Hours),Client,Project,Task,Work Type,Billable,User,Note\n";
        foreach ($logs as $log) {
            $hours = round($log->duration_minutes / 60, 2);
            $billable = $log->is_billable ? 'Yes' : 'No';
            $note = str_replace('"', '""', $log->note ?? '');
            $csv .= sprintf(
                '"%s","%.2f","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $log->date,
                $hours,
                $log->client_name ?? 'N/A',
                $log->project_name ?? 'N/A',
                $log->task_name ?? 'N/A',
                $log->work_type,
                $billable,
                $log->user_name ?? 'Unknown',
                $note
            );
        }

        $filename = 'worklog_export_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
