@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="h-full flex items-center justify-center p-4">
        <div class="bg-beige-50 w-full max-w-lg rounded-lg shadow-xl border border-gray-300 overflow-hidden">
            <!-- Header -->
            <div class="bg-beige-50 px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 font-serif">New Entry</h2>
                    <p class="text-sm text-gray-500">Record your session manually.</p>
                </div>
                <a href="{{ route('dooz.worklog.index') }}" class="text-gray-400 hover:text-gray-600">
                    <i class="ph ph-x text-xl"></i>
                </a>
            </div>

            <form action="{{ route('dooz.worklog.store') }}" method="POST" class="p-6">
                @csrf

                <!-- Date -->
                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2" for="date">
                        Date
                    </label>
                    <div class="relative">
                        <input
                            class="appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-900 leading-tight focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white font-medium"
                            id="date" name="date" type="text" value="{{ now()->format('M d, Y (l)') }}" readonly>
                        <i class="ph ph-calendar-blank absolute right-4 top-3.5 text-gray-400 text-lg"></i>
                    </div>
                </div>

                <div class="flex gap-4 mb-5">
                    <!-- Duration -->
                    <div class="w-2/3">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2" for="duration">
                            Duration
                        </label>
                        <input
                            class="appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-900 leading-tight focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white font-mono text-lg"
                            id="duration" name="duration" type="number" value="45" placeholder="0">
                    </div>
                    <!-- Unit -->
                    <div class="w-1/3">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2" for="unit">
                            Unit
                        </label>
                        <div class="relative">
                            <select name="unit"
                                class="appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-900 leading-tight focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white font-medium">
                                <option value="min">min</option>
                                <option value="hr">hr</option>
                            </select>
                            <i class="ph ph-caret-down absolute right-3 top-4 text-gray-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Project -->
                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2" for="project_id">
                        Project / Context
                    </label>
                    <div class="relative">
                        <select name="project_id" id="project_id"
                            class="appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-900 leading-tight focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white font-medium shadow-sm transition-shadow hover:shadow-md cursor-pointer"
                            required>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->client_name }} - {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        <i class="ph ph-folder absolute right-4 top-3.5 text-gray-500"></i>
                    </div>
                </div>

                <!-- Task Name (as Notes/description equivalent in mock) -->
                <div class="mb-5">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2" for="name">
                        Notes / Task Description
                    </label>
                    <textarea name="name" id="name" rows="4"
                        class="appearance-none border border-gray-300 rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white bg-opacity-50 resize-none"
                        placeholder="Describe the work completed..."></textarea>
                </div>

                <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('dooz.worklog.index') }}"
                        class="text-gray-600 hover:text-gray-900 font-medium py-2 px-6 rounded transition-colors">
                        Cancel
                    </a>
                    <button
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow-sm flex items-center gap-2"
                        type="submit">
                        <i class="ph-bold ph-floppy-disk"></i>
                        Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
