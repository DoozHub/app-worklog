<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportWebController extends Controller
{
    public function index(Request $request)
    {
        // 1. Filter Logic
        $clientId = $request->input('client_id');
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now()->endOfMonth();

        // 2. Base Query
        $query = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select(
                'worklog_time_logs.*',
                'worklog_tasks.name as task_name',
                'worklog_tasks.is_billable',
                'worklog_projects.name as project_name',
                'worklog_projects.hourly_rate',
                'worklog_clients.name as client_name',
                'worklog_clients.billing_type'
            )
            ->whereBetween('worklog_time_logs.date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($clientId) {
            $query->where('worklog_clients.id', $clientId);
        }

        $logs = $query->orderBy('worklog_time_logs.date', 'desc')->get();

        // 3. Metrics Calculation
        $totalMinutes = $logs->sum('duration_minutes');
        $totalHours = round($totalMinutes / 60, 1);
        
        // Dynamic Rate Logic
        $billableAmount = $logs->reduce(function ($carry, $log) {
            if (!$log->is_billable) return $carry;
            
            $rate = 0;
            if ($log->billing_type === 'hourly') {
                // Use Project Rate if set, otherwise default to 0 (or some fallback)
                $rate = $log->hourly_rate ?? 0;
            }
            // if ($log->billing_type === 'fixed') $rate = 0; 
            
            return $carry + (($log->duration_minutes / 60) * $rate);
        }, 0);

        $activeClientsCount = $logs->unique('client_name')->count();
        
        // Billable Ratio
        $billableMinutes = $logs->where('is_billable', true)->sum('duration_minutes');
        $billableRatio = $totalMinutes > 0 ? round(($billableMinutes / $totalMinutes) * 100) : 0;

        // 4. Data for View
        $clients = DB::table('worklog_clients')->where('is_active', true)->get();

        return view('dooz-worklog::reports.index', [
            'logs' => $logs,
            'clients' => $clients,
            'totalHours' => $totalHours,
            'billableAmount' => $billableAmount,
            'activeClientsCount' => $activeClientsCount,
            'billableRatio' => $billableRatio,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedClientId' => $clientId
        ]);
    }

    public function weekly(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::now();
        $startDate = $date->copy()->startOfWeek();
        $endDate = $date->copy()->endOfWeek();

        $logs = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select(
                'worklog_time_logs.*',
                'worklog_tasks.name as task_name',
                'worklog_tasks.is_billable',
                'worklog_projects.name as project_name',
                'worklog_clients.billing_type'
            )
            ->whereBetween('worklog_time_logs.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('worklog_time_logs.date', 'desc')
            ->orderBy('worklog_time_logs.created_at', 'desc')
            ->get();

        // Metrics
        $totalMinutes = $logs->sum('duration_minutes');
        $totalHours = floor($totalMinutes / 60);
        $totalMins = $totalMinutes % 60;
        $totalFormatted = sprintf('%dh %02dm', $totalHours, $totalMins);

        $billableMinutes = $logs->where('is_billable', 1)->sum('duration_minutes');
        $billableHours = floor($billableMinutes / 60);
        $billableMins = $billableMinutes % 60;
        $billableFormatted = sprintf('%dh %02dm', $billableHours, $billableMins);

        $earnings = $logs->reduce(function ($carry, $log) {
            if (!$log->is_billable) return $carry;
            $rate = ($log->billing_type === 'hourly') ? 100 : 0;
            return $carry + (($log->duration_minutes / 60) * $rate);
        }, 0);

        // Group by Date for View
        $dailyLogs = [];
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
        
        // Reverse iteration to show newest days first (like the mockup)
        $dates = array_reverse(iterator_to_array($period));

        foreach ($dates as $dt) {
            $dateStr = $dt->toDateString();
            $dayLogs = $logs->where('date', $dateStr);
            
            if ($dayLogs->isEmpty() && !$dt->isToday()) continue; // Skip empty days unless it's today

            $dayMinutes = $dayLogs->sum('duration_minutes');
            $dayH = floor($dayMinutes / 60);
            $dayM = $dayMinutes % 60;

            $dailyLogs[$dateStr] = [
                'isToday' => $dt->isToday(),
                'totalFormatted' => sprintf('%dh %02dm', $dayH, $dayM),
                'logs' => $dayLogs
            ];
        }

        return view('dooz-worklog::reports.weekly', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dailyLogs' => $dailyLogs,
            'totalFormatted' => $totalFormatted,
            'billableFormatted' => $billableFormatted,
            'earnings' => $earnings
        ]);
    }

    /**
     * Export time logs as CSV
     */
    public function exportCsv(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now()->endOfMonth();
        $clientId = $request->input('client_id');

        $query = DB::table('worklog_time_logs')
            ->leftJoin('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->leftJoin('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->leftJoin('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->leftJoin('users', 'worklog_time_logs.user_id', '=', 'users.id')
            ->select(
                'worklog_time_logs.date',
                'worklog_time_logs.duration_minutes',
                'worklog_time_logs.work_type',
                'worklog_time_logs.note',
                'worklog_tasks.name as task_name',
                'worklog_tasks.is_billable',
                'worklog_tasks.is_billable',
                'worklog_projects.name as project_name',
                'worklog_projects.hourly_rate',
                'worklog_clients.name as client_name',
                'worklog_clients.billing_type',
                'users.name as user_name'
            )
            ->whereBetween('worklog_time_logs.date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($clientId) {
            $query->where('worklog_clients.id', $clientId);
        }

        $logs = $query->orderBy('worklog_time_logs.date', 'desc')->get();

        // Build CSV
        $csv = "Date,Duration (Hours),Client,Project,Task,Work Type,Billable,Rate,Amount,User,Note\n";
        foreach ($logs as $log) {
            $hours = round($log->duration_minutes / 60, 2);
            $billable = $log->is_billable ? 'Yes' : 'No';
            
            // Rate Logic
            $rate = 0;
            if ($log->is_billable) { 
                 if (isset($log->billing_type) && $log->billing_type == 'hourly') {
                     $rate = $log->hourly_rate ?? 0;
                 }
            }
            // Check if client is hourly
            // Wait, exportCsv query needs to select client billing_type and project details correctly.
            
            $amount = $hours * $rate;

            $note = str_replace('"', '""', $log->note ?? '');
            $csv .= sprintf(
                '"%s","%.2f","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $log->date,
                $hours,
                $log->client_name ?? 'N/A',
                $log->project_name ?? 'N/A',
                $log->task_name ?? 'N/A',
                $log->work_type,
                $billable,
                $rate,
                number_format($amount, 2),
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

