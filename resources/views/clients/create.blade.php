@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-10 text-border-b border-gray-800 pb-4 border-b-2 border-black">
            <p class="text-xs font-mono uppercase text-gray-500 mb-1">Administration / Clients</p>
            <h1 class="text-5xl text-gray-900">{{ isset($client) ? 'Edit Client' : 'New Client' }}</h1>
        </div>

        <div class="bg-white border border-gray-300 p-8 shadow-sm">
            <form
                action="{{ isset($client) ? route('dooz.worklog.clients.update', $client->id) : route('dooz.worklog.clients.store') }}"
                method="POST">
                @csrf
                @if (isset($client))
                    @method('PUT')
                @endif

                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Client Name</label>
                    <input type="text" name="name" value="{{ $client->name ?? old('name') }}"
                        class="w-full bg-beige-50 border border-gray-300 p-3 font-serif text-xl focus:outline-none focus:border-black transition-colors"
                        placeholder="e.g. Acme Corp" required>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Billing Type</label>
                    <div class="grid grid-cols-3 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="billing_type" value="hourly" class="peer sr-only"
                                {{ isset($client) && $client->billing_type == 'hourly' ? 'checked' : 'checked' }}>
                            <div
                                class="border border-gray-300 p-4 text-center peer-checked:bg-black peer-checked:text-white peer-checked:border-black hover:bg-beige-50 transition-all">
                                <span class="font-mono text-sm">Hourly</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="billing_type" value="fixed" class="peer sr-only"
                                {{ isset($client) && $client->billing_type == 'fixed' ? 'checked' : '' }}>
                            <div
                                class="border border-gray-300 p-4 text-center peer-checked:bg-black peer-checked:text-white peer-checked:border-black hover:bg-beige-50 transition-all">
                                <span class="font-mono text-sm">Fixed</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="billing_type" value="internal" class="peer sr-only"
                                {{ isset($client) && $client->billing_type == 'internal' ? 'checked' : '' }}>
                            <div
                                class="border border-gray-300 p-4 text-center peer-checked:bg-black peer-checked:text-white peer-checked:border-black hover:bg-beige-50 transition-all">
                                <span class="font-mono text-sm">Internal</span>
                            </div>
                        </label>
                    </div>
                </div>

                @if (isset($client))
                    <div class="mb-8">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1"
                                class="w-4 h-4 text-black border-gray-300 focus:ring-black"
                                {{ $client->is_active ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700 font-mono">Active Client</span>
                        </label>
                    </div>
                @endif

                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('dooz.worklog.clients.index') }}"
                        class="text-sm text-gray-500 hover:text-black underline font-mono">Cancel</a>
                    <button type="submit"
                        class="bg-gray-900 text-white px-8 py-3 font-mono text-sm uppercase tracking-wide hover:bg-black transition-colors">
                        {{ isset($client) ? 'Save Changes' : 'Create Client' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
