@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-10 text-border-b border-gray-800 pb-4 border-b-2 border-black flex justify-between items-end">
            <div>
                <p class="text-xs font-mono uppercase text-gray-500 mb-1">Administration</p>
                <h1 class="text-5xl text-gray-900">Clients</h1>
            </div>
            <a href="{{ route('dooz.worklog.clients.create') }}"
                class="bg-gray-900 text-white px-6 py-3 flex items-center group hover:bg-black transition-colors font-mono text-sm uppercase tracking-wide">
                <i class="ph ph-plus mr-2 text-lg"></i>
                New Client
            </a>
        </div>

        <div class="bg-white border border-gray-300 shadow-sm">
            <div
                class="grid grid-cols-12 gap-4 p-4 border-b border-gray-300 bg-beige-200 text-xs font-bold uppercase tracking-wider text-gray-600">
                <div class="col-span-4">Name</div>
                <div class="col-span-3">Billing Type</div>
                <div class="col-span-3">Status</div>
                <div class="col-span-2 text-right">Actions</div>
            </div>

            @forelse($clients as $client)
                <div
                    class="grid grid-cols-12 gap-4 p-4 border-b border-gray-100 items-center hover:bg-beige-50 transition-colors">
                    <div class="col-span-4 font-bold text-lg font-serif">{{ $client->name }}</div>
                    <div class="col-span-3 font-mono text-sm">
                        <span
                            class="inline-block px-2 py-1 bg-gray-100 text-gray-700 rounded-sm text-xs uppercase">{{ $client->billing_type }}</span>
                    </div>
                    <div class="col-span-3">
                        @if ($client->is_active)
                            <span class="text-green-600 font-mono text-xs flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span> Active
                            </span>
                        @else
                            <span class="text-gray-400 font-mono text-xs flex items-center">
                                <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span> Inactive
                            </span>
                        @endif
                    </div>
                    <div class="col-span-2 text-right">
                        <a href="{{ route('dooz.worklog.clients.edit', $client->id) }}"
                            class="text-gray-400 hover:text-black transition-colors">
                            <i class="ph ph-pencil-simple text-xl"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500 italic">No clients found. Start by creating one.</div>
            @endforelse
        </div>
    </div>
@endsection
