@extends('environment-manager::layouts.app')

@section('title', 'Diff Viewer')

@section('content')
<h1 class="text-xl font-semibold mb-6">Diff Viewer</h1>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6 mb-6">
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Compare the current <code>.env</code> file with any saved backup, or compare two backups side by side.</p>
    <form method="GET" class="flex flex-wrap gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Source A</label>
            <select name="source_a" class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 w-64">
                <option value="">— select —</option>
                @foreach($sources as $value => $label)
                    <option value="{{ $value }}" @selected($sourceA === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Source B</label>
            <select name="source_b" class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 w-64">
                <option value="">— select —</option>
                @foreach($sources as $value => $label)
                    <option value="{{ $value }}" @selected($sourceB === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-1.5 rounded-md transition-colors">Compare</button>
        </div>
    </form>
</div>

@if(!empty($diff))
    @if(empty(array_filter($diff, fn($e) => $e['status'] !== 'unchanged')))
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <p class="text-3xl mb-2">✓</p>
            <p>The selected sources are identical.</p>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-750 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Key</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">{{ $labelA ?? 'Source A' }}</th>
                        <th class="px-4 py-3 text-left">{{ $labelB ?? 'Source B' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($diff as $key => $entry)
                        @if($entry['status'] !== 'unchanged')
                        <tr class="
                            @if($entry['status'] === 'added') bg-green-50 dark:bg-green-900/20
                            @elseif($entry['status'] === 'removed') bg-red-50 dark:bg-red-900/20
                            @elseif($entry['status'] === 'modified') bg-yellow-50 dark:bg-yellow-900/20
                            @endif
                        ">
                            <td class="px-4 py-2 font-mono text-xs font-medium">{{ $key }}</td>
                            <td class="px-4 py-2 text-xs font-medium
                                @if($entry['status'] === 'added') text-green-700 dark:text-green-400
                                @elseif($entry['status'] === 'removed') text-red-600 dark:text-red-400
                                @else text-yellow-700 dark:text-yellow-400
                                @endif">
                                {{ strtoupper($entry['status']) }}
                            </td>
                            <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $entry['old'] ?? '—' }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $entry['new'] ?? '—' }}</td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endif
@endsection
