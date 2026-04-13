@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <div class="text-xs font-mono uppercase text-gray-500 mb-1">Billing</div>
                <h1 class="text-4xl font-serif text-gray-900">Invoices</h1>
            </div>
            <a href="{{ route('dooz.worklog.invoices.create') }}"
                class="bg-black text-white px-6 py-3 rounded-sm text-sm font-bold uppercase flex items-center gap-2 hover:bg-gray-800 transition-colors">
                <i class="ph-fill ph-plus"></i> Generate Invoice
            </a>
        </div>

        <!-- Invoice Cards -->
        @if ($invoices->isEmpty())
            <div class="bg-white border border-gray-300 shadow-sm rounded-sm p-12 text-center">
                <div class="text-5xl mb-4">📄</div>
                <h3 class="text-xl font-serif text-gray-900 mb-2">No Invoices Yet</h3>
                <p class="text-gray-500 mb-6">Generate your first invoice from billable time logs.</p>
                <a href="{{ route('dooz.worklog.invoices.create') }}" class="text-blue-600 font-bold hover:underline">
                    Create Invoice →
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($invoices as $invoice)
                    @php
                        $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                            'sent' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'paid' => 'bg-green-50 text-green-700 border-green-200',
                            'cancelled' => 'bg-red-50 text-red-700 border-red-200',
                        ];
                        $colorClass = $statusColors[$invoice->status] ?? $statusColors['draft'];
                    @endphp
                    <a href="{{ route('dooz.worklog.invoices.show', $invoice->id) }}"
                        class="block bg-white border border-gray-300 shadow-sm rounded-sm p-6 hover:border-gray-400 transition-colors">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-6">
                                <div>
                                    <div class="font-mono text-sm text-gray-500">{{ $invoice->invoice_number }}</div>
                                    <div class="text-lg font-bold text-gray-900">{{ $invoice->client_name }}</div>
                                </div>
                                <span
                                    class="{{ $colorClass }} px-2 py-1 text-[10px] font-bold uppercase border rounded-sm">
                                    {{ $invoice->status }}
                                </span>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-mono font-bold text-gray-900">
                                    ${{ number_format($invoice->total_amount, 2) }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($invoice->period_start)->format('M d') }} -
                                    {{ \Carbon\Carbon::parse($invoice->period_end)->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
