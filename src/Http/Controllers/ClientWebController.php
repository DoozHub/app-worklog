<?php

namespace Dooz\Worklog\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ClientWebController extends Controller
{
    public function index()
    {
        $clients = DB::table('worklog_clients')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dooz-worklog::clients.index', ['clients' => $clients]);
    }

    public function create()
    {
        return view('dooz-worklog::clients.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'billing_type' => 'required|in:hourly,fixed,internal',
        ]);

        DB::table('worklog_clients')->insert([
            'name' => $request->input('name'),
            'billing_type' => $request->input('billing_type'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dooz.worklog.clients.index')->with('success', 'Client created successfully.');
    }

    public function edit($id)
    {
        $client = DB::table('worklog_clients')->where('id', $id)->first();
        if (!$client) {
            abort(404);
        }
        return view('dooz-worklog::clients.create', ['client' => $client]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'billing_type' => 'required|in:hourly,fixed,internal',
            'is_active' => 'boolean',
        ]);

        DB::table('worklog_clients')->where('id', $id)->update([
            'name' => $request->input('name'),
            'billing_type' => $request->input('billing_type'),
            'is_active' => $request->has('is_active'),
            'updated_at' => now(),
        ]);

        return redirect()->route('dooz.worklog.clients.index')->with('success', 'Client updated successfully.');
    }
}
