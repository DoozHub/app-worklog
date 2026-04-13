@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <div class="text-xs font-mono uppercase text-gray-500 mb-1">
                    <a href="{{ route('dooz.worklog.invoices.index') }}" class="hover:underline">Invoices</a> /
                    {{ $invoice->invoice_number }}
                </div>
                <h1 class="text-4xl font-serif text-gray-900">Invoice Details</h1>
            </div>
            @php
                $statusColors = [
                    'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                    'sent' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'paid' => 'bg-green-50 text-green-700 border-green-200',
                    'cancelled' => 'bg-red-50 text-red-700 border-red-200',
                ];
                $colorClass = $statusColors[$invoice->status] ?? $statusColors['draft'];
            @endphp
            <span class="{{ $colorClass }} px-4 py-2 text-sm font-bold uppercase border rounded-sm">
                {{ $invoice->status }}
            </span>
        </div>

        <!-- Invoice Card -->
        <div class="bg-white border border-gray-300 shadow-sm rounded-sm overflow-hidden mb-6">
            <!-- Invoice Header -->
            <div class="p-8 border-b border-gray-200">
                <div class="flex justify-between">
                    <div>
                        <div class="text-xs font-bold uppercase text-gray-500 mb-1">Client</div>
                        <div class="text-2xl font-bold text-gray-900">{{ $invoice->client_name }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-mono text-lg text-gray-500">{{ $invoice->invoice_number }}</div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ \Carbon\Carbon::parse($invoice->period_start)->format('M d') }} -
                            {{ \Carbon\Carbon::parse($invoice->period_end)->format('M d, Y') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items -->
            <div class="divide-y divide-gray-100">
                <div class="px-8 py-3 bg-gray-50 grid grid-cols-4 text-xs font-bold uppercase text-gray-500">
                    <div class="col-span-2">Description</div>
                    <div class="text-center">Date</div>
                    <div class="text-right">Time</div>
                </div>
                @foreach ($logs as $log)
                    <div class="px-8 py-4 grid grid-cols-4 items-center hover:bg-beige-50">
                        <div class="col-span-2">
                            <div class="font-medium text-gray-900">{{ $log->task_name }}</div>
                            <div class="text-xs text-gray-500">{{ $log->project_name }}</div>
                        </div>
                        <div class="text-center text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($log->date)->format('M d') }}
                        </div>
                        <div class="text-right font-mono text-gray-900">
                            {{ floor($log->duration_minutes / 60) }}h {{ $log->duration_minutes % 60 }}m
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Totals -->
            <div class="p-8 bg-gray-50 border-t border-gray-200">
                <div class="flex justify-between items-end">
                    <div>
                        <div class="text-xs font-bold uppercase text-gray-500 mb-1">Total Time</div>
                        <div class="text-xl font-mono text-gray-900">
                            {{ floor($invoice->total_minutes / 60) }}h {{ $invoice->total_minutes % 60 }}m
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-bold uppercase text-gray-500 mb-1">Amount Due</div>
                        <div class="text-4xl font-mono font-bold text-green-600">
                            ${{ number_format($invoice->total_amount, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between items-center">
            <a href="{{ route('dooz.worklog.invoices.index') }}"
                class="text-gray-500 hover:text-gray-900 text-sm font-bold">
                ← Back to Invoices
            </a>

            @if ($invoice->status === 'draft')
                <div class="flex gap-3">
                    <form action="{{ route('dooz.worklog.invoices.updateStatus', $invoice->id) }}" method="POST"
                        class="inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="sent">
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 text-sm font-bold rounded-sm hover:bg-blue-700 transition-colors">
                            Mark as Sent
                        </button>
                    </form>
                </div>
            @elseif($invoice->status === 'sent')
                <form action="{{ route('dooz.worklog.invoices.updateStatus', $invoice->id) }}" method="POST"
                    class="inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="paid">
                    <button type="submit"
                        class="bg-green-600 text-white px-6 py-2 text-sm font-bold rounded-sm hover:bg-green-700 transition-colors">
                        Mark as Paid
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
