<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * InvoiceController - API controller for Invoice operations
 */
class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = DB::table('worklog_invoices')
            ->leftJoin('worklog_clients', 'worklog_invoices.client_id', '=', 'worklog_clients.id')
            ->select(
                'worklog_invoices.*',
                'worklog_clients.name as client_name'
            )
            ->orderBy('worklog_invoices.created_at', 'desc')
            ->get();

        return response()->json($invoices);
    }

    public function show($id)
    {
        $invoice = DB::table('worklog_invoices')
            ->leftJoin('worklog_clients', 'worklog_invoices.client_id', '=', 'worklog_clients.id')
            ->select('worklog_invoices.*', 'worklog_clients.name as client_name')
            ->where('worklog_invoices.id', $id)
            ->first();

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        // Get associated time logs
        $logs = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->where('worklog_time_logs.invoice_id', $id)
            ->select(
                'worklog_time_logs.*',
                'worklog_tasks.name as task_name',
                'worklog_projects.name as project_name'
            )
            ->orderBy('worklog_time_logs.date')
            ->get();

        $invoice->time_logs = $logs;

        return response()->json($invoice);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:worklog_clients,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $clientId = $request->input('client_id');
        $periodStart = Carbon::parse($request->input('period_start'));
        $periodEnd = Carbon::parse($request->input('period_end'));

        // Get billable time logs in period
        $logs = DB::table('worklog_time_logs')
            ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
            ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
            ->where('worklog_projects.client_id', $clientId)
            ->where('worklog_tasks.is_billable', true)
            ->whereNull('worklog_time_logs.invoice_id')
            ->whereBetween('worklog_time_logs.date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        if ($logs->isEmpty()) {
            return response()->json([
                'error' => 'No billable time logs found for the selected period.'
            ], 400);
        }

        $totalMinutes = $logs->sum('duration_minutes');
        $totalAmount = round(($totalMinutes / 60) * 100, 2); // $100/hr default

        // Create invoice
        $invoiceId = DB::table('worklog_invoices')->insertGetId([
            'client_id' => $clientId,
            'invoice_number' => 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_minutes' => $totalMinutes,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link time logs to invoice
        $logIds = $logs->pluck('id')->toArray();
        DB::table('worklog_time_logs')->whereIn('id', $logIds)->update([
            'invoice_id' => $invoiceId
        ]);

        return response()->json([
            'id' => $invoiceId,
            'message' => 'Invoice created successfully',
            'log_count' => count($logIds),
            'total_hours' => round($totalMinutes / 60, 1),
            'total_amount' => $totalAmount
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,paid,cancelled'
        ]);

        $invoice = DB::table('worklog_invoices')->where('id', $id)->first();
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        DB::table('worklog_invoices')->where('id', $id)->update([
            'status' => $request->input('status'),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Invoice status updated',
            'status' => $request->input('status')
        ]);
    }
}
