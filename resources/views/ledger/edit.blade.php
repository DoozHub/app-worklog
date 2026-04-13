@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="text-xs font-mono uppercase text-gray-500 mb-1">
                <a href="{{ route('dooz.worklog.tasks.index') }}" class="hover:underline">My Tasks</a> /
                <a href="{{ route('dooz.worklog.tasks.show', $task->id) }}"
                    class="hover:underline">Task-{{ $task->id }}</a> / Edit
            </div>
            <h1 class="text-4xl text-gray-900">Edit Task</h1>
        </div>

        <!-- Form Card -->
        <div class="bg-white border border-gray-300 shadow-sm rounded-sm p-8">
            <form action="{{ route('dooz.worklog.tasks.update', $task->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Task Name -->
                <div class="mb-6">
                    <label for="name"
                        class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-2">Task Name *</label>
                    <input type="text" name="name" id="name" required
                        class="w-full bg-beige-50 border border-gray-300 p-3 text-sm font-medium rounded-sm focus:outline-none focus:border-black"
                        placeholder="e.g., Q3 Financial Reconciliation" value="{{ old('name', $task->name) }}">
                </div>

                <!-- Project Selection -->
                <div class="mb-6">
                    <label for="project_id"
                        class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-2">Project *</label>
                    <select name="project_id" id="project_id" required
                        class="w-full bg-beige-50 border border-gray-300 p-3 text-sm font-medium rounded-sm focus:outline-none focus:border-black appearance-none">
                        <option value="" disabled>Select a project...</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}"
                                {{ old('project_id', $task->project_id) == $project->id ? 'selected' : '' }}>
                                {{ $project->name }} ({{ $project->client_name ?? 'No Client' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Assignee Selection -->
                <div class="mb-6">
                    <label for="assignee_id"
                        class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-2">Assignee</label>
                    <select name="assignee_id" id="assignee_id"
                        class="w-full bg-beige-50 border border-gray-300 p-3 text-sm font-medium rounded-sm focus:outline-none focus:border-black appearance-none">
                        <option value="" {{ !$task->assignee_id ? 'selected' : '' }}>Unassigned</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old('assignee_id', $task->assignee_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label for="description"
                        class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-2">Description</label>
                    <textarea name="description" id="description" rows="4"
                        class="w-full bg-beige-50 border border-gray-300 p-3 text-sm font-mono rounded-sm focus:outline-none focus:border-black resize-none"
                        placeholder="Describe the task scope, deliverables, and any important notes...">{{ old('description', $task->description) }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Due Date -->
                    <div>
                        <label for="due_date"
                            class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-2">Due Date</label>
                        <input type="date" name="due_date" id="due_date"
                            class="w-full bg-beige-50 border border-gray-300 p-3 text-sm font-mono rounded-sm focus:outline-none focus:border-black"
                            value="{{ old('due_date', $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') : '') }}">
                    </div>

                    <!-- Estimated Time -->
                    <div>
                        <label for="estimated_minutes"
                            class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-2">Estimated Time
                            (minutes)</label>
                        <input type="number" name="estimated_minutes" id="estimated_minutes" min="0"
                            class="w-full bg-beige-50 border border-gray-300 p-3 text-sm font-mono rounded-sm focus:outline-none focus:border-black"
                            placeholder="e.g., 240" value="{{ old('estimated_minutes', $task->estimated_minutes) }}">
                    </div>
                </div>

                <!-- Billable Toggle -->
                <div class="mb-8 flex items-center gap-3">
                    <input type="checkbox" name="is_billable" id="is_billable" value="1"
                        {{ old('is_billable', $task->is_billable) ? 'checked' : '' }}
                        class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="is_billable" class="text-sm font-medium text-gray-700">This task is billable</label>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-4 pt-6 border-t border-gray-100">
                    <a href="{{ route('dooz.worklog.tasks.show', $task->id) }}"
                        class="bg-white border border-gray-300 text-gray-700 px-6 py-2.5 text-sm font-bold uppercase rounded-sm hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="bg-black text-white px-6 py-2.5 text-sm font-bold uppercase rounded-sm hover:bg-gray-800 transition-colors flex items-center gap-2">
                        <i class="ph-fill ph-check"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
