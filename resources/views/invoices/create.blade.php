@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="text-xs font-mono uppercase text-gray-500 mb-1">
                <a href="{{ route('dooz.worklog.invoices.index') }}" class="hover:underline">Invoices</a> / New
            </div>
            <h1 class="text-4xl font-serif text-gray-900">Generate Invoice</h1>
        </div>

        <!-- Client Selection Card -->
        <div class="bg-white border border-gray-300 shadow-sm rounded-sm p-8 mb-6">
            <h3 class="text-lg font-serif text-gray-900 mb-4">1. Select Client</h3>
            <form action="{{ route('dooz.worklog.invoices.create') }}" method="GET" class="flex gap-4">
                <select name="client_id"
                    class="flex-1 bg-beige-50 border border-gray-300 p-3 text-sm rounded-sm focus:outline-none focus:border-black">
                    <option value="">Select a client...</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" {{ $selectedClientId == $client->id ? 'selected' : '' }}>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                    class="bg-gray-100 border border-gray-300 px-6 py-3 text-sm font-bold rounded-sm hover:bg-gray-200 transition-colors">
                    Load Unbilled Logs
                </button>
            </form>
        </div>

        @if ($selectedClientId)
            <!-- Unbilled Logs Preview -->
            <div class="bg-white border border-gray-300 shadow-sm rounded-sm p-8 mb-6">
                <h3 class="text-lg font-serif text-gray-900 mb-4">2. Review Unbilled Time</h3>

                @if ($unbilledLogs->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <div class="text-3xl mb-2">✓</div>
                        <p>No unbilled time logs for this client.</p>
                    </div>
                @else
                    <div class="border border-gray-200 rounded-sm divide-y divide-gray-100 mb-6">
                        @foreach ($unbilledLogs->take(10) as $log)
                            <div class="p-4 flex justify-between items-center">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $log->task_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->project_name }} •
                                        {{ \Carbon\Carbon::parse($log->date)->format('M d') }}</div>
                                </div>
                                <div class="font-mono text-gray-700">
                                    {{ floor($log->duration_minutes / 60) }}h {{ $log->duration_minutes % 60 }}m
                                </div>
                            </div>
                        @endforeach
                        @if ($unbilledLogs->count() > 10)
                            <div class="p-4 text-center text-gray-500 text-sm">
                                + {{ $unbilledLogs->count() - 10 }} more entries
                            </div>
                        @endif
                    </div>

                    <!-- Summary -->
                    <div class="bg-gray-50 border border-gray-200 rounded-sm p-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <div class="text-xs font-bold uppercase text-gray-500 mb-1">Total Time</div>
                                <div class="text-2xl font-mono font-bold text-gray-900">
                                    {{ floor($totalMinutes / 60) }}h {{ $totalMinutes % 60 }}m
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase text-gray-500 mb-1">Estimated Amount</div>
                                <div class="text-2xl font-mono font-bold text-green-600">
                                    ${{ number_format($totalAmount, 2) }}
                                </div>
                                <div class="text-xs text-gray-400">@ $100/hr</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($unbilledLogs->isNotEmpty())
                <!-- Generate Form -->
                <div class="bg-white border border-gray-300 shadow-sm rounded-sm p-8">
                    <h3 class="text-lg font-serif text-gray-900 mb-4">3. Generate Invoice</h3>
                    <form action="{{ route('dooz.worklog.invoices.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="client_id" value="{{ $selectedClientId }}">

                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Period Start</label>
                                <input type="date" name="period_start" required
                                    class="w-full bg-beige-50 border border-gray-300 p-3 text-sm rounded-sm focus:outline-none focus:border-black"
                                    value="{{ $unbilledLogs->min('date') }}">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Period End</label>
                                <input type="date" name="period_end" required
                                    class="w-full bg-beige-50 border border-gray-300 p-3 text-sm rounded-sm focus:outline-none focus:border-black"
                                    value="{{ $unbilledLogs->max('date') }}">
                            </div>
                        </div>

                        <div class="flex justify-end gap-4">
                            <a href="{{ route('dooz.worklog.invoices.index') }}"
                                class="bg-white border border-gray-300 text-gray-700 px-6 py-3 text-sm font-bold uppercase rounded-sm hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit"
                                class="bg-green-600 text-white px-8 py-3 text-sm font-bold uppercase rounded-sm hover:bg-green-700 transition-colors flex items-center gap-2">
                                <i class="ph-fill ph-file-text"></i> Generate Invoice
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        @endif
    </div>
@endsection
