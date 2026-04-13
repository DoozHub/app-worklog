<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * InvoiceWebController - Basic Invoice Management
 * 
 * Generates invoices from billable time logs for clients.
 * Future scope: Payment integration, PDF generation, recurring invoices.
 */
class InvoiceWebController extends Controller
{
    /**
     * List all invoices
     */
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

        return view('dooz-worklog::invoices.index', ['invoices' => $invoices]);
    }

    /**
     * Show invoice creation form
     */
    public function create(Request $request)
    {
        $clients = DB::table('worklog_clients')->where('is_active', true)->get();
        $selectedClientId = $request->input('client_id');
        
        // Get unbilled time logs for preview
        $unbilledLogs = [];
        $totalMinutes = 0;
        $totalAmount = 0;
        
        if ($selectedClientId) {
            $unbilledLogs = DB::table('worklog_time_logs')
                ->join('worklog_tasks', 'worklog_time_logs.task_id', '=', 'worklog_tasks.id')
                ->join('worklog_projects', 'worklog_tasks.project_id', '=', 'worklog_projects.id')
                ->where('worklog_projects.client_id', $selectedClientId)
                ->where('worklog_tasks.is_billable', true)
                ->whereNull('worklog_time_logs.invoice_id')
                ->select(
                    'worklog_time_logs.*',
                    'worklog_tasks.name as task_name',
                    'worklog_projects.name as project_name'
                )
                ->orderBy('worklog_time_logs.date', 'desc')
                ->get();
            
            $totalMinutes = $unbilledLogs->sum('duration_minutes');
            // Assuming $100/hr for hourly clients
            $totalAmount = round(($totalMinutes / 60) * 100, 2);
        }

        return view('dooz-worklog::invoices.create', [
            'clients' => $clients,
            'selectedClientId' => $selectedClientId,
            'unbilledLogs' => $unbilledLogs,
            'totalMinutes' => $totalMinutes,
            'totalAmount' => $totalAmount
        ]);
    }

    /**
     * Generate invoice from selected time logs
     */
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
            return redirect()->back()->withErrors(['No billable time logs found for the selected period.']);
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

        return redirect()->route('dooz.worklog.invoices.show', $invoiceId)
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * View invoice details
     */
    public function show($id)
    {
        $invoice = DB::table('worklog_invoices')
            ->leftJoin('worklog_clients', 'worklog_invoices.client_id', '=', 'worklog_clients.id')
            ->select('worklog_invoices.*', 'worklog_clients.name as client_name')
            ->where('worklog_invoices.id', $id)
            ->first();

        if (!$invoice) abort(404);

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

        return view('dooz-worklog::invoices.show', [
            'invoice' => $invoice,
            'logs' => $logs
        ]);
    }

    /**
     * Update invoice status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,paid,cancelled'
        ]);

        DB::table('worklog_invoices')->where('id', $id)->update([
            'status' => $request->input('status'),
            'updated_at' => now()
        ]);

        return redirect()->back()->with('success', 'Invoice status updated.');
    }
}
