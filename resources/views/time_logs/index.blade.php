@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-5xl text-gray-900 mb-2">Time Logs: History</h1>
            <p class="text-gray-500 font-mono text-sm">A complete ledger of your work history.</p>
        </div>

        <div class="flex justify-end gap-3 mb-8">
            <button
                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 text-xs font-bold uppercase rounded-sm hover:bg-gray-50 flex items-center">
                <i class="ph ph-download-simple mr-2"></i> Export CSV
            </button>
            <a href="{{ route('dooz.worklog.time-logs.create') }}"
                class="bg-blue-600 text-white px-4 py-2 text-xs font-bold uppercase rounded-sm hover:bg-blue-700 flex items-center transition-colors">
                <i class="ph ph-plus mr-2"></i> Add Entry
            </a>
        </div>

        <!-- Metrics Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white border border-gray-300 p-6 rounded-sm shadow-sm">
                <div class="flex items-center text-xs font-bold uppercase text-gray-500 mb-2">
                    <i class="ph-fill ph-clock mr-2"></i> Total Hours
                </div>
                <div class="text-4xl font-serif mb-1">
                    {{ $totalHours }}<span class="text-lg text-gray-400 font-sans ml-1">h</span>
                </div>
                <div class="text-xs text-gray-500 font-mono mt-2">This Month</div>
            </div>

            <div class="bg-white border border-gray-300 p-6 rounded-sm shadow-sm">
                <div class="flex items-center text-xs font-bold uppercase text-gray-500 mb-2">
                    <i class="ph-fill ph-currency-dollar mr-2"></i> Billable Hours
                </div>
                <div class="text-4xl font-serif mb-1">
                    {{ $billableHours }}<span class="text-lg text-gray-400 font-sans ml-1">h</span>
                </div>
                <div class="text-xs text-gray-500 font-mono mt-2">{{ $billableRatio }}% Ratio</div>
            </div>

            <div class="bg-white border border-gray-300 p-6 rounded-sm shadow-sm">
                <div class="flex items-center text-xs font-bold uppercase text-gray-500 mb-2">
                    <i class="ph-fill ph-briefcase mr-2"></i> Projects Active
                </div>
                <div class="text-4xl font-serif mb-1">{{ $activeProjectsCount }}</div>
                <div class="text-xs text-gray-500 font-mono mt-2">Across {{ $clients->count() }} Clients</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white border border-gray-300 p-4 rounded-sm mb-6 flex flex-wrap gap-4 items-center">
            <form method="GET" action="{{ route('dooz.worklog.time-logs.index') }}" class="contents">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Date
                        Range</label>
                    <div class="flex gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                            class="w-full bg-beige-50 border border-gray-300 p-2 text-xs font-mono rounded-sm">
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                            class="w-full bg-beige-50 border border-gray-300 p-2 text-xs font-mono rounded-sm">
                    </div>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Client</label>
                    <select name="client_id"
                        class="w-full bg-beige-50 border border-gray-300 p-2 text-xs font-bold uppercase rounded-sm">
                        <option value="">All Clients</option>
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Project</label>
                    <select name="project_id"
                        class="w-full bg-beige-50 border border-gray-300 p-2 text-xs font-bold uppercase rounded-sm">
                        <option value="">All Projects</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end pb-0.5">
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 text-xs font-bold uppercase rounded-sm hover:bg-blue-700">Filter</button>
                    <a href="{{ route('dooz.worklog.time-logs.index') }}"
                        class="ml-2 bg-white border border-gray-300 text-gray-600 px-4 py-2 text-xs font-bold uppercase rounded-sm hover:bg-gray-50">Clear</a>
                </div>
            </form>
        </div>

        <!-- Log List -->
        <div class="bg-white border border-gray-300 shadow-sm">
            <div
                class="grid grid-cols-12 gap-4 p-4 border-b border-gray-300 bg-beige-200 text-xs font-bold uppercase tracking-wider text-gray-600">
                <div class="col-span-2">Date</div>
                <div class="col-span-1">Duration</div>
                <div class="col-span-2">Client</div>
                <div class="col-span-2">Project</div>
                <div class="col-span-4">Task Description</div>
                <div class="col-span-1 text-right">Type</div>
            </div>

            @forelse($logs as $log)
                <div
                    class="grid grid-cols-12 gap-4 p-4 border-b border-gray-100 items-center hover:bg-beige-50 transition-colors text-sm">
                    <div class="col-span-2 font-mono text-gray-600">
                        {{ \Carbon\Carbon::parse($log->date)->format('M d, Y') }}</div>
                    <div class="col-span-1 font-bold text-blue-600 font-mono">
                        {{ number_format($log->duration_minutes / 60, 2) }}h</div>
                    <div class="col-span-2 font-bold">{{ $log->client_name }}</div>
                    <div class="col-span-2 text-gray-600">{{ $log->project_name }}</div>
                    <div class="col-span-4">
                        <div class="font-medium text-gray-900">{{ $log->task_name }}</div>
                        @if ($log->note)
                            <div class="text-xs text-gray-500 truncate">{{ $log->note }}</div>
                        @endif
                    </div>
                    <div class="col-span-1 text-right">
                        <span
                            class="bg-blue-50 text-blue-700 px-2 py-1 rounded-sm text-[10px] uppercase font-bold border border-blue-100">{{ $log->work_type }}</span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500 italic">No logs found.</div>
            @endforelse

            <div class="p-4 bg-beige-50 border-t border-gray-300 text-xs text-gray-400 font-mono">
                Showing {{ $logs->count() }} logs
            </div>
        </div>
    </div>
@endsection
