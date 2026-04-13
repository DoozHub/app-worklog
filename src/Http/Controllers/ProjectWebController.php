<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ProjectWebController extends Controller
{
    public function index()
    {
        $projects = DB::table('worklog_projects')
            ->join('worklog_clients', 'worklog_projects.client_id', '=', 'worklog_clients.id')
            ->select('worklog_projects.*', 'worklog_clients.name as client_name')
            ->orderBy('worklog_projects.created_at', 'desc')
            ->get();

        return view('dooz-worklog::projects.index', ['projects' => $projects]);
    }

    public function create()
    {
        $clients = DB::table('worklog_clients')->where('is_active', true)->get();
        return view('dooz-worklog::projects.create', ['clients' => $clients]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:worklog_clients,id',
            'status' => 'required|in:active,paused,closed',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        DB::table('worklog_projects')->insert([
            'name' => $request->input('name'),
            'client_id' => $request->input('client_id'),
            'status' => $request->input('status'),
            'hourly_rate' => $request->input('hourly_rate', 0),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dooz.worklog.projects.index')->with('success', 'Project created successfully.');
    }

    public function edit($id)
    {
        $project = DB::table('worklog_projects')->where('id', $id)->first();
        if (!$project) {
            abort(404);
        }
        $clients = DB::table('worklog_clients')->where('is_active', true)->get();
        return view('dooz-worklog::projects.create', ['project' => $project, 'clients' => $clients]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:worklog_clients,id',
            'status' => 'required|in:active,paused,closed',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        DB::table('worklog_projects')->where('id', $id)->update([
            'name' => $request->input('name'),
            'client_id' => $request->input('client_id'),
            'status' => $request->input('status'),
            'hourly_rate' => $request->input('hourly_rate', 0),
            'updated_at' => now(),
        ]);

        return redirect()->route('dooz.worklog.projects.index')->with('success', 'Project updated successfully.');
    }
}
