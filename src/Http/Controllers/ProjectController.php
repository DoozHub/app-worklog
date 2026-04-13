<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('worklog_projects')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select(
                'worklog_projects.*',
                'worklog_clients.name as client_name'
            );

        // Optionally filter by status
        if (!$request->has('all')) {
            $query->where('worklog_projects.status', 'active');
        }

        return response()->json($query->orderBy('worklog_projects.name')->get());
    }

    public function show($id)
    {
        $project = DB::table('worklog_projects')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select('worklog_projects.*', 'worklog_clients.name as client_name')
            ->where('worklog_projects.id', $id)
            ->first();

        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        return response()->json($project);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:worklog_clients,id',
        ]);

        $id = DB::table('worklog_projects')->insertGetId([
            'name' => $request->input('name'),
            'client_id' => $request->input('client_id'),
            'status' => $request->input('status', 'active'),
            'billing_model' => $request->input('billing_model'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['id' => $id, 'message' => 'Project created'], 201);
    }

    public function update(Request $request, $id)
    {
        $project = DB::table('worklog_projects')->where('id', $id)->first();
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $request->input('name');
        if ($request->has('status')) $updateData['status'] = $request->input('status');
        if ($request->has('billing_model')) $updateData['billing_model'] = $request->input('billing_model');
        if ($request->has('start_date')) $updateData['start_date'] = $request->input('start_date');
        if ($request->has('end_date')) $updateData['end_date'] = $request->input('end_date');
        $updateData['updated_at'] = now();

        DB::table('worklog_projects')->where('id', $id)->update($updateData);

        return response()->json(['message' => 'Project updated']);
    }

    public function destroy($id)
    {
        $project = DB::table('worklog_projects')->where('id', $id)->first();
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        // Check if project has tasks
        $taskCount = DB::table('worklog_tasks')->where('project_id', $id)->count();
        if ($taskCount > 0) {
            return response()->json([
                'error' => 'Cannot delete project with existing tasks',
                'task_count' => $taskCount
            ], 400);
        }

        DB::table('worklog_projects')->where('id', $id)->delete();
        return response()->json(['message' => 'Project deleted']);
    }

    /**
     * Get projects for a specific client
     */
    public function byClient($clientId)
    {
        $projects = DB::table('worklog_projects')
            ->where('client_id', $clientId)
            ->orderBy('name')
            ->get();

        return response()->json($projects);
    }
}
