@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-4xl mx-auto">
        <!-- Breadcrumb / Header -->
        <div class="mb-6">
            <div class="text-xs font-mono uppercase text-gray-500 mb-1">Dooz Worklog / Time Logs</div>
            <h1 class="text-4xl text-gray-900 mb-4">Time Logs: Today</h1>

            <div
                class="flex items-center gap-4 text-sm font-mono text-gray-600 bg-white border border-gray-300 inline-flex p-2 rounded-sm shadow-sm">
                <button class="hover:text-black"><i class="ph ph-caret-left"></i></button>
                <div class="font-bold">{{ $today->format('F d, Y') }}</div>
                <button class="hover:text-black"><i class="ph ph-caret-right"></i></button>
                <span
                    class="bg-beige-200 px-2 py-0.5 rounded text-[10px] uppercase font-bold">{{ $today->format('l') }}</span>
            </div>
        </div>

        <!-- Quick Entry Bar -->
        <div class="bg-white border border-gray-300 p-2 rounded-sm shadow-sm mb-8">
            <form action="{{ route('dooz.worklog.time-logs.store') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <div class="p-2 text-gray-400"><i class="ph ph-plus"></i></div>

                <!-- Task Selector -->
                <div class="flex-1 relative group">
                    <select name="task_id"
                        class="w-full appearance-none bg-transparent font-medium text-gray-700 focus:outline-none p-2 cursor-pointer"
                        required>
                        <option value="" disabled selected>What are you working on?</option>
                        @foreach ($tasks as $task)
                            <option value="{{ $task->id }}">{{ $task->name }}
                                ({{ $task->project_name ?? 'No Project' }})
                            </option>
                        @endforeach
                    </select>
                    <i
                        class="ph ph-caret-down absolute right-2 top-3 text-gray-400 group-hover:text-black pointer-events-none"></i>
                </div>

                <div class="h-8 w-px bg-gray-200 mx-2"></div>

                <!-- Duration Input: Hours & Minutes -->
                <div class="flex items-center gap-1">
                    <input type="number" name="hours" placeholder="0" min="0" max="24"
                        class="w-12 bg-transparent font-mono text-right focus:outline-none p-2 text-gray-600">
                    <span class="text-xs text-gray-500 font-mono">h</span>
                    <input type="number" name="minutes" placeholder="00" min="0" max="59"
                        class="w-12 bg-transparent font-mono text-right focus:outline-none p-2 text-gray-600">
                    <span class="text-xs text-gray-500 font-mono mr-2">m</span>
                </div>

                <button type="submit"
                    class="bg-blue-500 text-white w-10 h-10 rounded-sm flex items-center justify-center hover:bg-blue-600 transition-colors">
                    <i class="ph ph-check text-xl"></i>
                </button>
            </form>
        </div>

        <!-- Daily Ledger List -->
        <div class="bg-white border border-gray-300 shadow-sm rounded-sm">
            <div
                class="grid grid-cols-12 gap-4 p-4 border-b border-gray-300 bg-beige-50 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                <div class="col-span-2">Time</div>
                <div class="col-span-6">Task Description</div>
                <div class="col-span-2">Project</div>
                <div class="col-span-2 text-right">Duration</div>
            </div>

            @forelse($logs as $log)
                <div
                    class="grid grid-cols-12 gap-4 p-5 border-b border-gray-100 items-center hover:bg-gray-50 transition-colors">
                    <div class="col-span-2 font-mono text-sm text-gray-600">
                        {{-- Mock Time Range for display since we only have duration --}}
                        {{ \Carbon\Carbon::parse($log->created_at)->format('H:i') }}
                    </div>
                    <div class="col-span-6">
                        <div class="flex items-start">
                            <div class="w-2 h-2 rounded-full bg-purple-400 mt-1.5 mr-3 flex-shrink-0"></div>
                            <!-- Random color mock -->
                            <div>
                                <div class="font-medium text-gray-900">{{ $log->task_name }}</div>
                                @if ($log->note)
                                    <div class="text-xs text-gray-500 mt-1">{{ $log->note }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <span
                            class="bg-gray-100 text-gray-600 px-2 py-1 rounded-sm text-[10px] font-bold uppercase tracking-wide border border-gray-200">
                            {{ Str::limit($log->project_name, 10) }}
                        </span>
                    </div>
                    <div class="col-span-2 text-right font-mono font-bold text-gray-900">
                        {{ floor($log->duration_minutes / 60) }}h {{ $log->duration_minutes % 60 }}m
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-gray-400 italic">
                    No logs for today yet. Start working!
                </div>
            @endforelse

            <!-- Total Footer -->
            <div class="p-4 bg-beige-100 border-t border-gray-300 flex justify-end items-center">
                <span class="text-xs font-bold uppercase text-gray-500 mr-4">Total Logged Today</span>
                <span class="text-2xl font-mono font-bold text-gray-900">{{ $totalFormatted }}</span>
            </div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-xs italic text-gray-400 serif">"Time is the most valuable thing a man can spend." — Theophrastus
            </p>
        </div>
    </div>
@endsection
