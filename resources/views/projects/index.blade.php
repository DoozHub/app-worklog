@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-10 text-border-b border-gray-800 pb-4 border-b-2 border-black flex justify-between items-end">
            <div>
                <p class="text-xs font-mono uppercase text-gray-500 mb-1">Administration</p>
                <h1 class="text-5xl text-gray-900">Projects</h1>
            </div>
            <a href="{{ route('dooz.worklog.projects.create') }}"
                class="bg-gray-900 text-white px-6 py-3 flex items-center group hover:bg-black transition-colors font-mono text-sm uppercase tracking-wide">
                <i class="ph ph-plus mr-2 text-lg"></i>
                New Project
            </a>
        </div>

        <div class="bg-white border border-gray-300 shadow-sm">
            <div
                class="grid grid-cols-12 gap-4 p-4 border-b border-gray-300 bg-beige-200 text-xs font-bold uppercase tracking-wider text-gray-600">
                <div class="col-span-5">Project Name</div>
                <div class="col-span-3">Client</div>
                <div class="col-span-2">Status</div>
                <div class="col-span-2 text-right">Actions</div>
            </div>

            @forelse($projects as $project)
                <div
                    class="grid grid-cols-12 gap-4 p-4 border-b border-gray-100 items-center hover:bg-beige-50 transition-colors">
                    <div class="col-span-5 font-bold text-lg font-serif">{{ $project->name }}</div>
                    <div class="col-span-3">
                        <span
                            class="inline-block px-2 py-1 bg-blue-50 text-blue-800 rounded-sm text-xs font-bold uppercase tracking-wide font-mono">
                            {{ $project->client_name }}
                        </span>
                    </div>
                    <div class="col-span-2">
                        @php
                            $statusColors = [
                                'active' => 'text-green-600 bg-green-500',
                                'paused' => 'text-yellow-600 bg-yellow-500',
                                'closed' => 'text-gray-400 bg-gray-400',
                            ];
                            $color = $statusColors[$project->status] ?? $statusColors['closed'];
                            // Extract just the bg color for the dot
                            $dotColor = explode(' ', $color)[1];
                            $textColor = explode(' ', $color)[0];
                        @endphp
                        <span class="{{ $textColor }} font-mono text-xs flex items-center uppercase">
                            <span class="w-2 h-2 {{ $dotColor }} rounded-full mr-2"></span> {{ $project->status }}
                        </span>
                    </div>
                    <div class="col-span-2 text-right">
                        <a href="{{ route('dooz.worklog.projects.edit', $project->id) }}"
                            class="text-gray-400 hover:text-black transition-colors">
                            <i class="ph ph-pencil-simple text-xl"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500 italic">No projects found.</div>
            @endforelse
        </div>
    </div>
@endsection
