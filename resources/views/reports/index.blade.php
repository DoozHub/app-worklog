@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-6xl mx-auto">
        <!-- Header & Filters -->
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <p class="text-xs font-mono uppercase text-gray-500 mb-1">Reports</p>
                <h1 class="text-5xl text-gray-900">Monthly Report</h1>
                <p class="text-sm text-gray-500 mt-2 font-mono">
                    {{ $startDate->format('M d') }} - {{ $endDate->format('M d, Y') }} • Summary of billable and
                    non-billable hours
                </p>
            </div>

            <form method="GET" action="{{ route('dooz.worklog.reports.index') }}" class="flex flex-wrap items-center gap-3">
                <!-- Date Range (Simplified as Month Select for now to match UI "Oct 1 - Oct 31" selector vibe) -->
                <!-- In a real app we'd use a date picker library. Using native date inputs for MVP -->
                <div class="flex items-center bg-white border border-gray-300 rounded-sm px-3 py-2">
                    <i class="ph ph-calendar text-gray-400 mr-2"></i>
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}"
                        class="text-xs font-mono outline-none w-24">
                    <span class="text-gray-400 mx-1">-</span>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}"
                        class="text-xs font-mono outline-none w-24">
                </div>

                <!-- Client Filter -->
                <div class="relative">
                    <select name="client_id" onchange="this.form.submit()"
                        class="appearance-none bg-white border border-gray-300 rounded-sm pl-9 pr-8 py-2 text-xs font-bold uppercase text-gray-700 focus:outline-none focus:border-black cursor-pointer">
                        <option value="">All Clients</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" {{ $selectedClientId == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="ph-fill ph-users absolute left-3 top-2.5 text-gray-400"></i>
                    <i class="ph ph-caret-down absolute right-3 top-2.5 text-gray-400"></i>
                </div>

                <button type="submit" formaction="{{ route('dooz.worklog.reports.export') }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded-sm text-xs font-bold uppercase tracking-wide flex items-center hover:bg-blue-700 transition-colors">
                    <i class="ph ph-download-simple mr-2"></i> Export CSV
                </button>
            </form>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Hours -->
            <div class="bg-beige-50 border border-gray-300 p-6 rounded-sm">
                <div class="flex items-center text-xs font-bold uppercase text-gray-500 mb-2">
                    <i class="ph-fill ph-clock mr-2"></i> Total Hours
                </div>
                <div class="text-4xl font-serif mb-1">
                    {{ $totalHours }}<span class="text-lg text-gray-400 font-sans ml-1">h</span>
                </div>
                <div class="text-xs text-green-600 font-mono mt-2">
                    <!-- Placeholder positive trend -->
                    +12% from last month
                </div>
            </div>

            <!-- Billable Amount -->
            <div class="bg-beige-50 border border-gray-300 p-6 rounded-sm">
                <div class="flex items-center text-xs font-bold uppercase text-gray-500 mb-2">
                    <i class="ph-fill ph-currency-dollar mr-2"></i> Billable Amount
                </div>
                <div class="text-4xl font-serif mb-1">
                    ${{ number_format($billableAmount, 2) }}
                </div>
                <div class="text-xs text-gray-500 font-mono mt-2">
                    {{ $billableRatio }}% billable ratio
                </div>
            </div>

            <!-- Active Clients -->
            <div class="bg-beige-50 border border-gray-300 p-6 rounded-sm">
                <div class="flex items-center text-xs font-bold uppercase text-gray-500 mb-2">
                    <i class="ph-fill ph-briefcase mr-2"></i> Active Clients
                </div>
                <div class="text-4xl font-serif mb-1">
                    {{ $activeClientsCount }}
                </div>
                <div class="text-xs text-gray-500 font-mono mt-2">
                    <!-- Placeholder count -->
                    {{ $clients->count() }} total clients
                </div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="mb-4">
            <div class="flex justify-between items-center mb-4 px-1">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500">Ledger Entries</h3>
                <div class="flex gap-2">
                    <button class="text-gray-400 hover:text-black"><i class="ph ph-printer text-lg"></i></button>
                    <button class="text-gray-400 hover:text-black"><i class="ph ph-sliders-horizontal text-lg"></i></button>
                </div>
            </div>

            <div class="bg-white border border-gray-300 shadow-sm">
                <!-- Table Header -->
                <div
                    class="grid grid-cols-12 gap-4 p-4 border-b border-gray-300 bg-beige-200 text-xs font-bold uppercase tracking-wider text-gray-600">
                    <div class="col-span-2">Date</div>
                    <div class="col-span-2">Client</div>
                    <div class="col-span-3">Project & Description</div>
                    <div class="col-span-1 text-right">Hours</div>
                    <div class="col-span-1 text-right">Rate</div>
                    <div class="col-span-1 text-right">Amount</div>
                    <div class="col-span-2 text-right">Status</div>
                </div>

                <!-- Table Rows -->
                @forelse($logs as $log)
                    <div
                        class="grid grid-cols-12 gap-4 p-4 border-b border-gray-100 items-center hover:bg-beige-50 transition-colors text-sm">
                        <div class="col-span-2 font-mono text-gray-600">
                            {{ \Carbon\Carbon::parse($log->date)->format('M d, Y') }}
                        </div>
                        <div class="col-span-2 font-bold text-gray-900">
                            {{ $log->client_name }}
                        </div>
                        <div class="col-span-3">
                            <div class="font-medium text-gray-900">{{ $log->project_name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ $log->note ?? $log->task_name }}</div>
                        </div>
                        <div class="col-span-1 text-right font-mono text-gray-600">
                            {{ number_format($log->duration_minutes / 60, 1) }}
                        </div>
                        <div class="col-span-1 text-right font-mono text-gray-400 text-xs">
                            @php
                                $rate = $log->billing_type === 'hourly' && $log->is_billable ? 100 : 0;
                            @endphp
                            {{ $log->is_billable ? '$' . $rate . '/h' : '-' }}
                        </div>
                        <div class="col-span-1 text-right font-mono font-bold text-gray-900">
                            @php
                                $amount = ($log->duration_minutes / 60) * $rate;
                            @endphp
                            {{ $amount > 0 ? '$' . number_format($amount, 2) : '$0.00' }}
                        </div>
                        <div class="col-span-2 text-right">
                            @if ($log->is_billable)
                                <span
                                    class="bg-green-100 text-green-800 px-2 py-1 rounded-sm text-xs font-bold uppercase tracking-wide border border-green-200">
                                    Billable
                                </span>
                            @else
                                <span
                                    class="bg-gray-100 text-gray-600 px-2 py-1 rounded-sm text-xs font-bold uppercase tracking-wide border border-gray-200">
                                    Non-Billable
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-gray-500 italic">
                        No time logs found for this period.
                    </div>
                @endforelse

                <!-- Footer Total -->
                <div class="grid grid-cols-12 gap-4 p-4 bg-beige-100 font-bold border-t border-gray-300">
                    <div class="col-span-7 text-right uppercase text-xs tracking-wider text-gray-500 pt-1">Grand Total</div>
                    <div class="col-span-1 text-right font-mono">{{ $totalHours }}</div>
                    <div class="col-span-1"></div>
                    <div class="col-span-1 text-right font-mono">${{ number_format($billableAmount, 2) }}</div>
                    <div class="col-span-2"></div>
                </div>
            </div>

            <div class="mt-4 text-xs text-gray-400 text-center font-mono">
                Showing {{ $logs->count() }} entries
            </div>
        </div>
    </div>
@endsection
