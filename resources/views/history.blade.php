@extends('environment-manager::layouts.app')

@section('title', 'Version History')

@section('content')
<h1 class="text-xl font-semibold mb-6">Version History</h1>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-6">
    <input type="text" name="key" value="{{ request('key') }}" placeholder="Filter by key..."
           class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-md px-3 py-1.5 text-sm w-48 focus:outline-none focus:ring-2 focus:ring-indigo-400">
    <input type="date" name="from" value="{{ request('from') }}"
           class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
    <input type="date" name="to" value="{{ request('to') }}"
           class="border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
    <button type="submit" class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-sm px-4 py-1.5 rounded-md">Filter</button>
</form>

<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        <thead class="bg-gray-50 dark:bg-gray-750 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
            <tr>
                <th class="px-4 py-3 text-left">Time</th>
                <th class="px-4 py-3 text-left">Action</th>
                <th class="px-4 py-3 text-left">Key</th>
                <th class="px-4 py-3 text-left">Old Value</th>
                <th class="px-4 py-3 text-left">New Value</th>
                <th class="px-4 py-3 text-left">User</th>
                <th class="px-4 py-3 text-left">Source</th>
                <th class="px-4 py-3 text-left">Reason</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($history as $record)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-2 text-xs text-gray-500 whitespace-nowrap">{{ $record->created_at->format('Y-m-d H:i:s') }}</td>
                <td class="px-4 py-2">
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                        @if($record->action === 'create') bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300
                        @elseif($record->action === 'delete') bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300
                        @elseif($record->action === 'restore') bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300
                        @else bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300
                        @endif">
                        {{ $record->action }}
                    </span>
                </td>
                <td class="px-4 py-2 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $record->key }}</td>
                <td class="px-4 py-2 font-mono text-xs text-gray-500 max-w-xs truncate">{{ $record->old_value ?? '—' }}</td>
                <td class="px-4 py-2 font-mono text-xs text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ $record->new_value ?? '—' }}</td>
                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">{{ $record->user_name ?? '—' }}</td>
                <td class="px-4 py-2 text-xs text-gray-500">{{ $record->source }}</td>
                <td class="px-4 py-2 text-xs text-gray-500 max-w-xs truncate">{{ $record->reason ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-gray-400">No history records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $history->withQueryString()->links() }}
</div>
@endsection
