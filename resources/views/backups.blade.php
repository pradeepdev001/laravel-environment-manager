@extends('environment-manager::layouts.app')

@section('title', 'Backups')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold">Backups</h1>
    <form method="POST" action="{{ route('env-manager.backups.create') }}">
        @csrf
        <button type="submit"
                class="inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors">
            + Create Backup
        </button>
    </form>
</div>

@if(empty($backups))
    <div class="text-center py-16 text-gray-500 dark:text-gray-400">
        <p class="text-4xl mb-3">💾</p>
        <p>No backups yet. Create one to get started.</p>
    </div>
@else
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-750 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Filename</th>
                    <th class="px-4 py-3 text-left">Size</th>
                    <th class="px-4 py-3 text-left">Created</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($backups as $backup)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="px-4 py-2 font-mono text-xs text-gray-800 dark:text-gray-200">
                        {{ $backup['filename'] }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-500">
                        {{ number_format($backup['size'] / 1024, 2) }} KB
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-500 whitespace-nowrap">
                        {{ $backup['created_at'] }}
                    </td>
                    <td class="px-4 py-2 text-right">
                        <div class="flex items-center justify-end gap-3">
                            {{-- Download --}}
                            <a href="{{ route('env-manager.backups.download', $backup['filename']) }}"
                               class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">Download</a>

                            {{-- Restore --}}
                            <form method="POST"
                                  action="{{ route('env-manager.backups.restore', $backup['filename']) }}"
                                  onsubmit="return confirm('Restore .env from this backup? Current .env will be overwritten.')">
                                @csrf
                                <button type="submit" class="text-yellow-600 dark:text-yellow-400 hover:underline text-xs">Restore</button>
                            </form>

                            {{-- Delete --}}
                            <form method="POST"
                                  action="{{ route('env-manager.backups.delete', $backup['filename']) }}"
                                  onsubmit="return confirm('Delete this backup?')">
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
@endif
@endsection
