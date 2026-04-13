@extends('dooz-worklog::layouts.worklog')

@section('worklog-content')
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-10 text-border-b border-gray-800 pb-4 border-b-2 border-black">
            <p class="text-xs font-mono uppercase text-gray-500 mb-1">Administration / Projects</p>
            <h1 class="text-5xl text-gray-900">{{ isset($project) ? 'Edit Project' : 'New Project' }}</h1>
        </div>

        <div class="bg-white border border-gray-300 p-8 shadow-sm">
            <form
                action="{{ isset($project) ? route('dooz.worklog.projects.update', $project->id) : route('dooz.worklog.projects.store') }}"
                method="POST">
                @csrf
                @if (isset($project))
                    @method('PUT')
                @endif

                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Project Name</label>
                    <input type="text" name="name" value="{{ $project->name ?? old('name') }}"
                        class="w-full bg-beige-50 border border-gray-300 p-3 font-serif text-xl focus:outline-none focus:border-black transition-colors"
                        placeholder="e.g. Website Redesign" required>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Client</label>
                    <select name="client_id"
                        class="w-full bg-beige-50 border border-gray-300 p-3 font-mono focus:outline-none focus:border-black transition-colors">
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}"
                                {{ isset($project) && $project->client_id == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Hourly Rate
                        (USD)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-500 font-serif">$</span>
                        <input type="number" name="hourly_rate" step="0.01" min="0"
                            value="{{ $project->hourly_rate ?? old('hourly_rate', 0) }}"
                            class="w-full bg-beige-50 border border-gray-300 p-3 pl-8 font-serif text-xl focus:outline-none focus:border-black transition-colors"
                            placeholder="0.00">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Used for billable reports.</p>
                </div>

                <div class="mb-8">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Status</label>
                    <div class="grid grid-cols-3 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="active" class="peer sr-only"
                                {{ isset($project) && $project->status == 'active' ? 'checked' : 'checked' }}>
                            <div
                                class="border border-gray-300 p-4 text-center peer-checked:bg-green-100 peer-checked:text-green-800 peer-checked:border-green-600 hover:bg-beige-50 transition-all">
                                <span class="font-mono text-sm uppercase">Active</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="paused" class="peer sr-only"
                                {{ isset($project) && $project->status == 'paused' ? 'checked' : '' }}>
                            <div
                                class="border border-gray-300 p-4 text-center peer-checked:bg-yellow-100 peer-checked:text-yellow-800 peer-checked:border-yellow-600 hover:bg-beige-50 transition-all">
                                <span class="font-mono text-sm uppercase">Paused</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="closed" class="peer sr-only"
                                {{ isset($project) && $project->status == 'closed' ? 'checked' : '' }}>
                            <div
                                class="border border-gray-300 p-4 text-center peer-checked:bg-gray-200 peer-checked:text-gray-800 peer-checked:border-gray-600 hover:bg-beige-50 transition-all">
                                <span class="font-mono text-sm uppercase">Closed</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('dooz.worklog.projects.index') }}"
                        class="text-sm text-gray-500 hover:text-black underline font-mono">Cancel</a>
                    <button type="submit"
                        class="bg-gray-900 text-white px-8 py-3 font-mono text-sm uppercase tracking-wide hover:bg-black transition-colors">
                        {{ isset($project) ? 'Save Changes' : 'Create Project' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
