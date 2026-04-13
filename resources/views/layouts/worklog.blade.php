@extends('layouts.tenant')


@section('app-sidebar')
    <div class="fixed top-14 bottom-0 left-0 w-64 bg-beige-100 border-r border-gray-200 z-30 flex flex-col">
        {{-- Brand --}}
        <div class="h-14 flex items-center px-4 border-b border-gray-300 flex-shrink-0">
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                <path
                    d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3ZM5 5H19V19H5V5Z" />
                <path d="M7 7H17" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                <path d="M7 11H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                <path d="M7 15H10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            <span class="font-bold text-sm tracking-tight">Worklog</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 mt-4 px-3 space-y-1 overflow-y-auto custom-scrollbar">
            <div class="px-2 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Workspace</div>

            <a href="{{ route('dooz.worklog.index') }}"
                class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('dooz.worklog.index') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200 hover:text-black' }}">
                <i class="ph-fill ph-squares-four mr-3 text-lg"></i>
                Dashboard
            </a>

            <a href="/worklog/time-logs/create"
                class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->is('worklog/time-logs*') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200 hover:text-black' }}">
                <i class="ph-fill ph-clock mr-3 text-lg"></i>
                Time Logs
            </a>

            <a href="/worklog/tasks"
                class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->is('worklog/tasks*') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200 hover:text-black' }}">
                <i class="ph-fill ph-list-checks mr-3 text-lg"></i>
                My Tasks
            </a>

            <a href="/worklog/projects"
                class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->is('worklog/projects*') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200 hover:text-black' }}">
                <i class="ph-fill ph-folder mr-3 text-lg"></i>
                Projects
            </a>

            <a href="/worklog/clients"
                class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->is('worklog/clients*') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200 hover:text-black' }}">
                <i class="ph-fill ph-buildings mr-3 text-lg"></i>
                Clients
            </a>

            <div class="px-2 mt-6 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Reports</div>

            <a href="/worklog/reports/weekly"
                class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->is('worklog/reports/weekly*') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200 hover:text-black' }}">
                <i class="ph-fill ph-calendar mr-3 text-lg"></i>
                Weekly Summary
            </a>

            <a href="/worklog/reports"
                class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->is('worklog/reports') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200 hover:text-black' }}">
                <i class="ph-fill ph-chart-bar mr-3 text-lg"></i>
                Monthly Report
            </a>

            <div class="px-2 mt-6 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Billing</div>

            <a href="/worklog/invoices"
                class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->is('worklog/invoices*') ? 'bg-gray-200 text-black' : 'text-gray-600 hover:bg-gray-200 hover:text-black' }}">
                <i class="ph-fill ph-receipt mr-3 text-lg"></i>
                Invoices
            </a>
        </nav>

        {{-- User --}}
        <div class="p-3 border-t border-gray-300">
            <div class="flex items-center">
                <div
                    class="h-8 w-8 bg-gray-200 border border-gray-400 rounded flex items-center justify-center font-bold text-gray-600 text-xs">
                    {{ substr(auth()->user()->name ?? 'AA', 0, 2) }}
                </div>
                <div class="ml-2">
                    <p class="text-xs font-medium text-gray-900">{{ auth()->user()->name ?? 'Guest' }}</p>
                    <div class="flex items-center">
                        <div class="h-1.5 w-1.5 rounded-full bg-green-500 mr-1.5"></div>
                        <p class="text-[10px] text-green-600 font-mono">Online</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- Custom Worklog Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Playfair+Display:wght@400;600;700&display=swap"
        rel="stylesheet">

    {{-- Phosphor Icons --}}
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    {{-- Extend Tailwind with beige colors --}}
    <script>
        // Add beige colors to Tailwind at runtime
        if (window.tailwind && window.tailwind.config) {
            window.tailwind.config.theme.extend.colors.beige = {
                50: '#fbfaf8',
                100: '#f5f2eb',
                200: '#e6e2d6',
                300: '#d1cbb8',
                400: '#a39e8c',
                900: '#333333',
            };
        }
    </script>

    <style>
        /* Beige color utility classes (fallback) */
        .bg-beige-50 {
            background-color: #fbfaf8 !important;
        }

        .bg-beige-100 {
            background-color: #f5f2eb !important;
        }

        .bg-beige-200 {
            background-color: #e6e2d6 !important;
        }

        .bg-beige-300 {
            background-color: #d1cbb8 !important;
        }

        .bg-beige-400 {
            background-color: #a39e8c !important;
        }

        .bg-beige-900 {
            background-color: #333333 !important;
        }

        .text-beige-50 {
            color: #fbfaf8 !important;
        }

        .text-beige-100 {
            color: #f5f2eb !important;
        }

        .text-beige-200 {
            color: #e6e2d6 !important;
        }

        .text-beige-300 {
            color: #d1cbb8 !important;
        }

        .text-beige-400 {
            color: #a39e8c !important;
        }

        .text-beige-900 {
            color: #333333 !important;
        }

        .border-beige-50 {
            border-color: #fbfaf8 !important;
        }

        .border-beige-100 {
            border-color: #f5f2eb !important;
        }

        .border-beige-200 {
            border-color: #e6e2d6 !important;
        }

        .border-beige-300 {
            border-color: #d1cbb8 !important;
        }

        .border-beige-400 {
            border-color: #a39e8c !important;
        }

        /* Worklog-specific theming */
        .worklog-content {
            font-family: 'IBM Plex Mono', monospace;
            color: #333;
        }

        .worklog-content h1,
        .worklog-content h2,
        .worklog-content h3,
        .worklog-content h4,
        .worklog-content h5,
        .worklog-content h6 {
            font-family: 'Playfair Display', serif;
        }
    </style>
@endpush

@section('content')
    <div class="worklog-content bg-beige-100 min-h-screen -mx-6 -my-6 p-8">
        @if ($errors->any())
            <div class="mb-8 bg-red-50 border-l-4 border-red-500 p-4 max-w-6xl mx-auto">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">There were problems with your submission:</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-8 bg-green-50 border-l-4 border-green-500 p-4 max-w-6xl mx-auto">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="ph-fill ph-check-circle text-green-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @yield('worklog-content')
    </div>
@endsection
