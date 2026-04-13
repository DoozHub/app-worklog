@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-10 text-border-b border-gray-800 pb-4 border-b-2 border-black">
            <p class="text-xs font-mono uppercase text-gray-500 mb-1">{{ now()->format('l, M d') }}</p>
            <div class="flex justify-between items-end">
                @php
                    $hour = now()->hour;
                    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
                @endphp
                <h1 class="text-5xl text-gray-900">{{ $greeting }}, {{ auth()->user()->name }}.</h1>
                <div class="font-mono text-sm">
                    System Status: <span class="text-green-600">● Online</span>
                </div>
            </div>
        </div>

        <!-- Metrics Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <!-- Card 1 -->
            <div class="bg-beige-100 border border-gray-300 p-6 relative rounded-sm shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-xs font-mono uppercase text-gray-500 tracking-wider">Tasks in Progress</span>
                    <i class="ph ph-clipboard-text text-3xl text-gray-300"></i>
                </div>
                <div class="text-5xl font-serif mb-4">{{ $inProgressCount }}</div>
                <div class="flex items-center text-xs font-mono text-gray-600">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                    Backend API Refactor
                </div>
            </div>

            <!-- Card 2 -->
            <div class="bg-beige-100 border border-gray-300 p-6 relative rounded-sm shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-xs font-mono uppercase text-gray-500 tracking-wider">On Hold</span>
                    <i class="ph ph-pause-circle text-3xl text-gray-300"></i>
                </div>
                <div class="text-5xl font-serif mb-4">{{ $blockedCount }}</div>
                <div class="flex items-center text-xs font-mono text-gray-600">
                    <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                    Waiting on Client
                </div>
            </div>

            <!-- Card 3 -->
            <div class="bg-beige-100 border border-gray-300 p-6 relative rounded-sm shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-xs font-mono uppercase text-gray-500 tracking-wider">Logged Today</span>
                    <i class="ph ph-timer text-3xl text-gray-300"></i>
                </div>
                <div class="text-5xl font-serif mb-4 flex items-baseline">
                    {{ $todayHours }}<span class="text-xl ml-1 mr-2">h</span> {{ $todayRemainingMinutes }}<span
                        class="text-xl ml-1">m</span>
                </div>
                <div
                    class="flex items-center text-xs font-mono text-gray-600 border-t border-dashed border-gray-300 pt-2 w-full">
                    <i class="ph ph-trend-up mr-2"></i> On track for 8h
                </div>
            </div>
        </div>

        <!-- Action Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <a href="{{ route('dooz.worklog.tasks.index') }}"
                class="md:col-span-2 bg-white border border-gray-300 p-8 flex items-center justify-between group cursor-pointer hover:border-gray-800 transition-colors">
                <div>
                    <h3 class="text-2xl font-bold mb-1">View My Tasks</h3>
                    <p class="text-gray-500 text-sm">Review your active queue and prioritize.</p>
                </div>
                <i class="ph ph-arrow-right text-2xl group-hover:translate-x-1 transition-transform"></i>
            </a>

            <a href="{{ route('dooz.worklog.create') }}"
                class="bg-gray-900 text-white p-8 flex items-center justify-between group hover:bg-black transition-colors">
                <div>
                    <h3 class="text-white text-2xl font-bold mb-1 font-sans">Log Time</h3>
                    <p class="text-gray-400 text-sm">Record your recent activity in the ledger.</p>
                </div>
                <i class="ph ph-pencil-simple text-2xl text-white"></i>
            </a>
        </div>

        <!-- Daily Ledger -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold flex items-center">
                    <i class="ph-fill ph-list-dashes mr-2"></i> Daily Ledger
                </h3>
                <a href="{{ route('dooz.worklog.time-logs.index') }}"
                    class="text-xs text-gray-500 underline hover:text-black">View Full Log</a>
            </div>

            <div class="border border-gray-300 bg-beige-50">
                <!-- Header -->
                <div
                    class="grid grid-cols-12 gap-4 p-4 border-b border-gray-300 bg-beige-200 text-xs font-bold uppercase tracking-wider text-gray-600">
                    <div class="col-span-2">Time</div>
                    <div class="col-span-5">Activity / Task</div>
                    <div class="col-span-3">Project</div>
                    <div class="col-span-2 text-right">Duration</div>
                </div>

                <!-- Rows -->
                @forelse($ledgerEntries as $entry)
                    <div
                        class="grid grid-cols-12 gap-4 p-4 border-b border-gray-200 items-center hover:bg-white transition-colors cursor-default">
                        <div class="col-span-2 font-mono text-sm text-gray-500">
                            {{ \Carbon\Carbon::parse($entry->created_at)->format('H:i A') }}</div>
                        <div class="col-span-5 font-medium text-gray-800">{{ $entry->task_name }}</div>
                        <div class="col-span-3">
                            <span
                                class="bg-gray-200 text-gray-700 px-2 py-1 text-xs uppercase tracking-wide rounded-sm font-bold">
                                {{ Str::limit($entry->project_name, 10) }}
                            </span>
                        </div>
                        <div class="col-span-2 text-right font-mono text-sm">
                            {{ floor($entry->duration_minutes / 60) }}h {{ $entry->duration_minutes % 60 }}m
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500 italic">No time logs for today.</div>
                @endforelse

                <!-- Current Running Mock -->
                <div class="grid grid-cols-12 gap-4 p-4 items-center bg-white">
                    <div class="col-span-2 font-mono text-sm text-gray-400">Currently</div>
                    <div class="col-span-5 font-medium text-gray-800 flex items-center">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                        Client Feedback Review
                    </div>
                    <div class="col-span-3">
                        <span
                            class="bg-blue-100 text-blue-700 px-2 py-1 text-xs uppercase tracking-wide rounded-sm font-bold">
                            Client A
                        </span>
                    </div>
                    <div class="col-span-2 text-right flex items-center justify-end">
                        <span class="font-mono text-sm mr-3">Running...</span>
                        <button class="w-6 h-6 border border-red-200 hover:border-red-500 flex items-center justify-center">
                            <div class="w-2 h-2 bg-red-500"></div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mt-2 text-xs text-gray-400 font-mono uppercase tracking-widest">
                <div>Showing {{ $ledgerEntries->count() }} entries for today</div>
                <div>Total: {{ $todayHours }}h {{ $todayRemainingMinutes }}m</div>
            </div>
        </div>

        <!-- Footer -->
        <div
            class="mt-16 pt-8 border-t border-gray-300 text-center text-xs font-mono text-gray-400 uppercase tracking-widest">
            Dooz Worklog v2.4.1 • Nonomous AI
        </div>
    </div>
@endsection
