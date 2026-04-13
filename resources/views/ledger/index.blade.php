@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-end mb-8">
            <div>
                <p class="text-xs font-mono uppercase text-gray-500 mb-1">Workspace: Default View • Updated just now</p>
                <h1 class="text-5xl text-gray-900">Task Ledger</h1>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('dooz.worklog.tasks.create') }}"
                    class="bg-gray-900 text-white px-4 py-2 rounded-sm text-sm font-medium flex items-center gap-2 hover:bg-gray-800 transition-colors">
                    <i class="ph ph-plus"></i> New Task
                </a>
                <div class="relative">
                    <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400"></i>
                    <input type="text" placeholder="Search entries..."
                        class="bg-white border border-gray-300 pl-9 pr-4 py-2 rounded-sm text-sm focus:outline-none focus:border-black w-64">
                </div>
                <button class="text-gray-500 hover:text-black"><i class="ph ph-funnel text-xl"></i></button>
                <button class="text-gray-500 hover:text-black"><i class="ph ph-gear text-xl"></i></button>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="bg-white border border-gray-300 shadow-sm rounded-sm">
            <div
                class="grid grid-cols-12 gap-4 p-4 border-b border-gray-300 bg-beige-50 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                <div class="col-span-1">#</div>
                <div class="col-span-5">Task Name</div>
                <div class="col-span-2">Client</div>
                <div class="col-span-2">Project</div>
                <div class="col-span-1 text-center">Status</div>
                <div class="col-span-1 text-right">Logged</div>
            </div>

            @forelse($tasks as $index => $task)
                <a href="{{ route('dooz.worklog.tasks.show', $task->id) }}"
                    class="grid grid-cols-12 gap-4 p-5 border-b border-gray-100 items-center hover:bg-beige-50 transition-colors group">
                    <div class="col-span-1 font-mono text-gray-400 text-sm">
                        {{ sprintf('%03d', $index + 1) }}
                    </div>
                    <div class="col-span-5">
                        <div class="font-bold text-gray-900 text-lg group-hover:text-blue-600 transition-colors">
                            {{ $task->name }}</div>
                        <div class="text-xs text-gray-500 mt-0.5 truncate pr-4">
                            {{ $task->description ?? 'No additional details provided...' }}
                        </div>
                    </div>
                    <div class="col-span-2 flex items-center">
                        <div
                            class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold mr-2 border border-blue-200">
                            {{ substr($task->client_name, 0, 1) }}
                        </div>
                        <span class="font-medium text-sm text-gray-700">{{ $task->client_name }}</span>
                    </div>
                    <div class="col-span-2 text-sm text-gray-600">
                        {{ $task->project_name }}
                    </div>
                    <div class="col-span-1 text-center">
                        @php
                            $statusColors = [
                                'new' => 'bg-gray-100 text-gray-600 border-gray-200',
                                'in_progress' => 'bg-blue-50 text-blue-600 border-blue-200',
                                'completed' => 'bg-green-50 text-green-600 border-green-200',
                                'on_hold' => 'bg-yellow-50 text-yellow-600 border-yellow-200',
                                'cancelled' => 'bg-red-50 text-red-600 border-red-200',
                            ];
                            $colorClass = $statusColors[$task->status] ?? $statusColors['new'];
                        @endphp
                        <span
                            class="{{ $colorClass }} px-2 py-1 rounded-full text-[10px] uppercase font-bold border block w-full">
                            {{ str_replace('_', ' ', $task->status) }}
                        </span>
                    </div>
                    <div class="col-span-1 text-right font-mono font-bold text-gray-900">
                        {{ $task->logged_label }}
                    </div>
                </a>
            @empty
                <div class="p-12 text-center text-gray-400 italic">
                    No tasks found. Create one in a Project!
                </div>
            @endforelse

            <div class="p-4 bg-beige-50 border-t border-gray-300 text-xs text-gray-400 font-mono flex justify-between">
                <span>Showing {{ $tasks->count() }} tasks</span>
                <div class="flex gap-2">
                    <button
                        class="bg-white border border-gray-300 px-3 py-1 rounded-sm hover:bg-gray-50 disabled:opacity-50">Previous</button>
                    <button class="bg-white border border-gray-300 px-3 py-1 rounded-sm hover:bg-gray-50">Next</button>
                </div>
            </div>
        </div>
    </div>
@endsection
