@extends('environment-manager::layouts.app')

@section('title', 'Import / Export')

@section('content')
<h1 class="text-xl font-semibold mb-6">Import / Export</h1>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Export --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <h2 class="font-medium text-gray-800 dark:text-gray-200 mb-4">Export</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Download your environment variables as a file. Sensitive values are masked by default.
        </p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('env-manager.export', 'env') }}"
               class="inline-flex items-center gap-1 border border-gray-300 dark:border-gray-600 hover:border-indigo-400 text-sm px-4 py-2 rounded-md transition-colors">
                ⬇ .env
            </a>
            <a href="{{ route('env-manager.export', 'json') }}"
               class="inline-flex items-center gap-1 border border-gray-300 dark:border-gray-600 hover:border-indigo-400 text-sm px-4 py-2 rounded-md transition-colors">
                ⬇ JSON
            </a>
            <a href="{{ route('env-manager.export', 'yaml') }}"
               class="inline-flex items-center gap-1 border border-gray-300 dark:border-gray-600 hover:border-indigo-400 text-sm px-4 py-2 rounded-md transition-colors">
                ⬇ YAML
            </a>
        </div>
    </div>

    {{-- Import --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <h2 class="font-medium text-gray-800 dark:text-gray-200 mb-4">Import</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Upload a <code>.env</code> or <code>JSON</code> file to merge variables. All variables are validated before any changes are applied.
        </p>
        <form method="POST" action="{{ route('env-manager.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Format</label>
                    <select name="format" class="border border-gray-300 dark:border-gray-600 dark:bg-gray-900 rounded-md px-3 py-1.5 text-sm w-full focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="env">.env format</option>
                        <option value="json">JSON format</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File</label>
                    <input type="file" name="file" accept=".env,.json,.txt"
                           class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border file:border-gray-300 file:text-sm file:bg-gray-50 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-200 hover:file:bg-gray-100 dark:hover:file:bg-gray-600">
                    @error('file')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="pt-1">
                    <button type="submit"
                            onclick="return confirm('Import will overwrite matching variables. Are you sure?')"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-md transition-colors w-full">
                        Import & Apply
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>
@endsection
