@extends('environment-manager::layouts.app')

@section('title', 'Environment Variables')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">Environment Variables</h1>
    <a href="{{ route('env-manager.create') }}"
       class="inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
        + Add Variable
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-6">
    <input type="text" name="search" value="{{ $search }}"
           placeholder="Search keys..."
           class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-md px-3 py-1.5 text-sm w-52 focus:outline-none focus:ring-2 focus:ring-indigo-400">

    <select name="category"
            class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        <option value="">All Categories</option>
        @foreach($categories as $cat)
            <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
        @endforeach
    </select>

    <button type="submit"
            class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-sm px-4 py-1.5 rounded-md transition-colors">
        Filter
    </button>

    @if($search || $category)
        <a href="{{ route('env-manager.index') }}"
           class="text-sm text-gray-500 dark:text-gray-400 hover:text-red-500 px-2 py-1.5">
            ✕ Clear
        </a>
    @endif
</form>

@forelse($grouped as $categoryName => $variables)
    <div class="mb-8">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-2">
            {{ $categoryName }}
            <span class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full px-2 py-0.5 text-xs font-normal">
                {{ $variables->count() }}
            </span>
        </h2>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-750">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 w-64">Key</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Value</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 w-20">Type</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-500 dark:text-gray-400 w-32">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($variables as $variable)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors" id="row-{{ $variable->key }}">
                        <td class="px-4 py-2 font-mono text-xs text-gray-800 dark:text-gray-200">
                            <div class="flex items-center gap-2">
                                {{ $variable->key }}
                                <button type="button"
                                        onclick="copyToClipboard('{{ $variable->key }}')"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        title="Copy key">
                                    📋
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs max-w-xs truncate">
                            @if($variable->sensitive)
                                <span class="value-masked-{{ $variable->key }}">
                                    <span class="text-gray-400 tracking-widest">••••••••</span>
                                    @if($canReveal)
                                        <button type="button"
                                                onclick="revealSecret('{{ $variable->key }}')"
                                                class="ml-2 text-indigo-500 hover:text-indigo-700 text-xs">
                                            👁 Reveal
                                        </button>
                                    @endif
                                </span>
                                <span class="value-revealed-{{ $variable->key }} hidden font-mono text-xs"></span>
                            @else
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $variable->rawValue ?: '(empty)' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <span class="inline-block px-1.5 py-0.5 rounded text-xs
                                @if($variable->type === 'boolean') bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300
                                @elseif($variable->type === 'integer') bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300
                                @elseif($variable->type === 'null') bg-gray-100 dark:bg-gray-700 text-gray-500
                                @else bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300
                                @endif">
                                {{ $variable->type }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('env-manager.edit', $variable->key) }}"
                                   class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">Edit</a>
                                <form method="POST" action="{{ route('env-manager.destroy', $variable->key) }}"
                                      onsubmit="return confirm('Delete {{ $variable->key }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 dark:hover:text-red-400 text-xs">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="text-center py-16 text-gray-500 dark:text-gray-400">
        <p class="text-4xl mb-3">🔍</p>
        <p>No variables found.</p>
        @if($search || $category)
            <a href="{{ route('env-manager.index') }}" class="text-indigo-500 hover:underline text-sm mt-2 inline-block">Clear filters</a>
        @else
            <a href="{{ route('env-manager.create') }}" class="text-indigo-500 hover:underline text-sm mt-2 inline-block">Add the first variable</a>
        @endif
    </div>
@endforelse
@endsection

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Brief visual feedback
        const btn = event.target;
        btn.textContent = '✓';
        setTimeout(() => btn.textContent = '📋', 1500);
    });
}

function revealSecret(key) {
    fetch(`{{ url(config('environment-manager.route_prefix', 'env-manager')) }}/${key}/reveal`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`.value-masked-${key}`).classList.add('hidden');
            const revealed = document.querySelector(`.value-revealed-${key}`);
            revealed.textContent = data.value || '(empty)';
            revealed.classList.remove('hidden');
        }
    });
}
</script>
@endpush
