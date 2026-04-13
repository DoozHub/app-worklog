@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="flex gap-6 max-w-full">
        <!-- Main Content (Left) -->
        <div class="flex-1 max-w-4xl">
            <!-- Breadcrumb -->
            <div class="text-xs font-mono text-gray-500 mb-6 flex items-center gap-2">
                <a href="{{ route('dooz.worklog.projects.index') }}" class="hover:underline">Projects</a>
                <span>/</span>
                <span>{{ $task->project_name }}</span>
                <span>/</span>
                <span class="text-gray-900 font-bold">Task-{{ $task->id }}</span>
            </div>

            <!-- Task Header Card -->
            <div class="bg-white border border-gray-300 shadow-sm rounded-sm p-8 mb-6 relative">
                <!-- ID & Status & Actions -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span
                            class="bg-gray-100 text-gray-600 px-2 py-1 text-[10px] font-mono border border-gray-200 rounded-sm">ID:
                            #{{ $task->id }}-{{ strtoupper(substr($task->name, 0, 1)) }}</span>
                        @php
                            $statusColors = [
                                'new' => 'bg-gray-100 text-gray-600 border-gray-200',
                                'in_progress' => 'bg-green-50 text-green-700 border-green-200',
                                'on_hold' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                'completed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'cancelled' => 'bg-red-50 text-red-700 border-red-200',
                            ];
                            $colorClass = $statusColors[$task->status] ?? $statusColors['new'];
                        @endphp
                        <span class="{{ $colorClass }} px-2 py-1 text-[10px] font-bold uppercase border rounded-sm">
                            {{ str_replace('_', ' ', $task->status) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('dooz.worklog.tasks.edit', $task->id) }}"
                            class="p-2 text-gray-500 hover:text-black"><i class="ph ph-pencil-simple"></i></a>
                        <button class="p-2 text-gray-500 hover:text-black"><i class="ph ph-paperclip"></i></button>
                        <button class="p-2 text-gray-500 hover:text-black"><i
                                class="ph ph-clock-counter-clockwise"></i></button>

                        @if ($canLogTime)
                            <a href="{{ route('dooz.worklog.time-logs.create') }}"
                                class="bg-green-600 text-white px-5 py-2 rounded-sm text-sm font-medium flex items-center hover:bg-green-700 transition-colors ml-2">
                                <i class="ph-fill ph-play mr-2"></i> Start Timer
                            </a>
                        @else
                            <span
                                class="bg-gray-200 text-gray-500 px-5 py-2 rounded-sm text-sm font-medium flex items-center ml-2 cursor-not-allowed">
                                <i class="ph ph-prohibit mr-2"></i> Logging Disabled
                            </span>
                        @endif

                        <!-- Status Dropdown -->
                        @if (count($availableTransitions) > 0)
                            <div class="relative" id="statusDropdownContainer">
                                <button onclick="toggleStatusDropdown()" type="button"
                                    class="p-2 px-3 text-gray-600 hover:text-black border border-gray-300 rounded-sm flex items-center gap-1">
                                    <i class="ph ph-arrows-clockwise"></i> Change Status <i
                                        class="ph ph-caret-down text-xs"></i>
                                </button>
                                <div id="statusDropdownMenu"
                                    class="hidden absolute right-0 mt-1 w-48 bg-white border border-gray-200 shadow-lg rounded-sm z-50">
                                    @foreach ($availableTransitions as $transition)
                                        <form action="{{ route('dooz.worklog.tasks.updateStatus', $task->id) }}"
                                            method="POST" class="block">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $transition['status'] }}">
                                            <button type="submit"
                                                class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 transition-colors">
                                                {{ $transition['label'] }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                            <script>
                                function toggleStatusDropdown() {
                                    const menu = document.getElementById('statusDropdownMenu');
                                    menu.classList.toggle('hidden');
                                }
                                // Close dropdown when clicking outside
                                document.addEventListener('click', function(e) {
                                    const container = document.getElementById('statusDropdownContainer');
                                    const menu = document.getElementById('statusDropdownMenu');
                                    if (container && menu && !container.contains(e.target)) {
                                        menu.classList.add('hidden');
                                    }
                                });
                            </script>
                        @endif
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-serif text-gray-900 mb-6">{{ $task->name }}</h1>

                <!-- Metadata Grid -->
                <div class="border border-gray-200 rounded-sm divide-y divide-gray-100">
                    <div class="grid grid-cols-4 p-4">
                        <div class="text-xs text-gray-500">Project</div>
                        <div class="col-span-3 text-sm font-medium text-gray-900">{{ $task->project_name }}</div>
                    </div>
                    <div class="grid grid-cols-4 p-4">
                        <div class="text-xs text-gray-500">Assigned to</div>
                        <div class="col-span-3 flex items-center gap-2">
                            @if ($task->assignee_name)
                                <div
                                    class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold">
                                    {{ strtoupper(substr($task->assignee_name, 0, 2)) }}
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $task->assignee_name }}</span>
                            @else
                                <span class="text-sm text-gray-400 italic">Unassigned</span>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-4 p-4">
                        <div class="text-xs text-gray-500">Description</div>
                        <div class="col-span-3 text-sm text-gray-700 leading-relaxed">
                            {{ $task->description ?? 'No description provided.' }}
                        </div>
                    </div>
                    <div class="grid grid-cols-4 p-4">
                        <div class="col-span-3 text-sm font-medium text-gray-900">
                            {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('M d, Y') : 'Not set' }}
                        </div>
                    </div>
                    @if (isset($task->linked_quicky_id) && $task->linked_quicky_id)
                        <div class="grid grid-cols-4 p-4">
                            <div class="text-xs text-gray-500">Linked Intent</div>
                            <div class="col-span-3 text-sm font-medium text-gray-900">
                                <a href="{{ route('dooz-quicky.show', $task->linked_quicky_id) }}"
                                    class="text-blue-600 hover:underline flex items-center gap-1">
                                    <i class="ph-fill ph-lightning"></i> Quicky #{{ $task->linked_quicky_id }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Logs Section -->
            <div class="bg-white border border-gray-300 shadow-sm rounded-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500">Recent Logs</h3>
                </div>

                @forelse($logs->take(5) as $log)
                    <div
                        class="px-6 py-4 border-b border-gray-100 flex justify-between items-center hover:bg-beige-50 transition-colors">
                        <div>
                            <div class="font-medium text-gray-900 text-sm">{{ $log->note ?? 'Work Session' }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ \Carbon\Carbon::parse($log->date)->format('M d') }} •
                                {{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}</div>
                        </div>
                        <div class="font-mono font-bold text-gray-900">
                            {{ floor($log->duration_minutes / 60) }}h
                            {{ str_pad($log->duration_minutes % 60, 2, '0', STR_PAD_LEFT) }}m
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400 italic">No logs for this task yet.</div>
                @endforelse
            </div>
        </div>

        <!-- Context & Insights (Right Sidebar) -->
        <div class="w-80 flex-shrink-0">
            <div class="bg-white border border-gray-300 shadow-sm rounded-sm sticky top-8">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 flex items-center gap-2">
                        <i class="ph ph-brain text-gray-400"></i> Context & Insights
                    </h3>
                    <button class="text-gray-400 hover:text-black"><i class="ph ph-arrows-out-simple"></i></button>
                </div>

                <!-- Estimation Baseline -->
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Estimation Baseline</div>
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-3 rounded-r-sm">
                        <p class="text-sm text-gray-700">Similar tasks historically took
                            <strong>{{ $task->estimated_minutes ? floor($task->estimated_minutes / 60) . 'h – ' . (floor($task->estimated_minutes / 60) + 1.5) . 'h' : '3h – 4.5h' }}</strong>
                            to complete.
                        </p>
                    </div>
                    <div class="flex gap-1 mt-3">
                        @for ($i = 0; $i < 7; $i++)
                            <div
                                class="h-2 flex-1 rounded-sm {{ $i < 5 ? 'bg-gray-200' : ($i == 5 ? 'bg-blue-500' : 'bg-gray-100') }}">
                            </div>
                        @endfor
                    </div>
                    <a href="#" class="text-xs text-gray-500 underline mt-2 inline-block">View source logs</a>
                </div>

                <!-- Cadence -->
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Cadence</div>
                    <div class="flex items-start gap-3 bg-gray-50 p-3 rounded-sm">
                        <i class="ph ph-clock text-gray-400 mt-0.5"></i>
                        <div>
                            <p class="text-sm text-gray-700">No activity recorded for
                                <strong>{{ $logs->isEmpty() ? '48 hours' : '0 hours' }}</strong>.
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Last active:
                                {{ $logs->first() ? \Carbon\Carbon::parse($logs->first()->created_at)->format('M d, H:i') : 'Never' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Deviation Check -->
                <div class="px-6 py-4">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-3">Deviation Check</div>
                    <div class="bg-gray-50 p-3 rounded-sm">
                        <p class="text-sm text-gray-700">Current session duration matches your typical focused work block
                            <span class="text-gray-400">(~90m)</span>.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 text-center">
                    <p class="text-[10px] text-gray-400 font-mono">Generated from local log data.</p>
                    <p class="text-[10px] text-gray-400 font-mono">Last updated: Just now.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
