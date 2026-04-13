<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * ClientController - API controller for Client operations
 */
class ClientController extends Controller
{
    public function index()
    {
        $clients = DB::table('worklog_clients')
            ->orderBy('name')
            ->get(['id', 'name', 'billing_type', 'is_active', 'created_at']);

        return response()->json($clients);
    }

    public function show($id)
    {
        $client = DB::table('worklog_clients')->where('id', $id)->first();
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }
        return response()->json($client);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'billing_type' => 'nullable|in:hourly,fixed,internal',
        ]);

        $id = DB::table('worklog_clients')->insertGetId([
            'name' => $request->input('name'),
            'billing_type' => $request->input('billing_type', 'hourly'),
            'is_active' => $request->input('is_active', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'id' => $id,
            'name' => $request->input('name'),
            'billing_type' => $request->input('billing_type', 'hourly'),
            'message' => 'Client created successfully'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'billing_type' => 'sometimes|in:hourly,fixed,internal',
            'is_active' => 'sometimes|boolean',
        ]);

        $client = DB::table('worklog_clients')->where('id', $id)->first();
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        DB::table('worklog_clients')->where('id', $id)->update([
            'name' => $request->input('name', $client->name),
            'billing_type' => $request->input('billing_type', $client->billing_type),
            'is_active' => $request->input('is_active', $client->is_active),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Client updated successfully']);
    }

    public function destroy($id)
    {
        $client = DB::table('worklog_clients')->where('id', $id)->first();
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        // Check if client has projects
        $projectCount = DB::table('worklog_projects')->where('client_id', $id)->count();
        if ($projectCount > 0) {
            return response()->json([
                'error' => 'Cannot delete client with existing projects',
                'project_count' => $projectCount
            ], 400);
        }

        DB::table('worklog_clients')->where('id', $id)->delete();
        return response()->json(['message' => 'Client deleted successfully']);
    }
}
