@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div class="flex items-center gap-4">
                <button class="hover:bg-gray-200 p-2 rounded-sm"><i class="ph ph-caret-left"></i></button>
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">Weekly Report</div>
                    <h1 class="text-3xl font-serif text-gray-900">
                        {{ $startDate->format('M d') }} - {{ $endDate->format('M d') }}
                    </h1>
                </div>
                <button class="hover:bg-gray-200 p-2 rounded-sm"><i class="ph ph-caret-right"></i></button>
                <span
                    class="bg-gray-100 text-gray-600 border border-gray-200 px-2 py-1 text-xs font-bold rounded-sm uppercase">Week
                    {{ $startDate->weekOfYear }}</span>
            </div>

            <div class="flex gap-4">
                <!-- Metrics -->
                <div class="text-right border-r border-gray-300 pr-4">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total</div>
                    <div class="text-xl font-mono font-bold text-gray-900">{{ $totalFormatted }}</div>
                </div>
                <div class="text-right border-r border-gray-300 pr-4">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Billable</div>
                    <div class="text-xl font-mono font-bold text-blue-600">{{ $billableFormatted }}</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Earnings</div>
                    <!-- Mock Earnings -->
                    <div class="text-xl font-mono font-bold text-green-700">${{ number_format($earnings, 0) }}</div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex gap-2">
                <button
                    class="bg-white border border-gray-300 px-3 py-1.5 rounded-sm text-xs font-bold uppercase flex items-center gap-2 hover:bg-gray-50">
                    <i class="ph ph-funnel"></i> Filter
                </button>
                <a href="{{ route('dooz.worklog.reports.export', ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}"
                    class="bg-white border border-gray-300 px-3 py-1.5 rounded-sm text-xs font-bold uppercase flex items-center gap-2 hover:bg-gray-50">
                    <i class="ph ph-download-simple"></i> Export CSV
                </a>
            </div>
            <div class="text-xs text-gray-500">
                Showing all entries for <strong>Week {{ $startDate->weekOfYear }}</strong>
            </div>
        </div>

        <!-- Daily Groups -->
        @foreach ($dailyLogs as $date => $dayData)
            <div class="bg-white border border-gray-300 shadow-sm rounded-sm mb-6 overflow-hidden">
                <!-- Day Header -->
                <div class="bg-beige-50 border-b border-gray-200 p-4 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full {{ $dayData['isToday'] ? 'bg-green-500' : 'bg-gray-400' }}"></div>
                        <h3 class="font-serif text-lg text-gray-900">
                            {{ \Carbon\Carbon::parse($date)->format('l, M d') }}
                            @if ($dayData['isToday'])
                                <span
                                    class="ml-2 bg-blue-100 text-blue-700 text-[10px] font-bold px-1.5 py-0.5 rounded-sm uppercase tracking-wide">Today</span>
                            @endif
                        </h3>
                    </div>
                    <div class="font-mono font-bold text-gray-900">{{ $dayData['totalFormatted'] }}</div>
                </div>

                <!-- Table Header -->
                <div
                    class="grid grid-cols-12 gap-4 px-6 py-2 border-b border-gray-100 bg-white text-[10px] font-bold uppercase tracking-wider text-gray-400">
                    <div class="col-span-2">Time</div>
                    <div class="col-span-1">Duration</div>
                    <div class="col-span-2">Project</div>
                    <div class="col-span-6">Task Description</div>
                    <div class="col-span-1 text-right">Edit</div>
                </div>

                <!-- Entries -->
                @forelse($dayData['logs'] as $log)
                    <div
                        class="grid grid-cols-12 gap-4 px-6 py-4 border-b border-gray-50 items-center hover:bg-beige-50 transition-colors group text-sm">
                        <div class="col-span-2 font-mono text-gray-500 text-xs">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($log->created_at)->addMinutes($log->duration_minutes)->format('H:i') }}
                        </div>
                        <div class="col-span-1 font-mono font-bold text-gray-900">
                            {{ floor($log->duration_minutes / 60) }}h {{ $log->duration_minutes % 60 }}m
                        </div>
                        <div class="col-span-2">
                            <span
                                class="bg-purple-50 text-purple-700 border border-purple-100 px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase">
                                {{ Str::limit($log->project_name, 12) }}
                            </span>
                        </div>
                        <div class="col-span-6 font-medium text-gray-800">
                            {{ $log->task_name }}
                        </div>
                        <div class="col-span-1 text-right opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="text-gray-400 hover:text-black"><i class="ph ph-pencil-simple"></i></button>
                        </div>
                    </div>
                @empty
                    <!-- Is this ever reached given the grouping? -->
                @endforelse

                @if ($dayData['isToday'])
                    <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 text-center">
                        <a href="{{ route('dooz.worklog.time-logs.create') }}"
                            class="text-xs font-bold uppercase text-gray-500 hover:text-blue-600 flex items-center justify-center gap-2">
                            <i class="ph-fill ph-plus-circle"></i> Add entry for today...
                        </a>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endsection
