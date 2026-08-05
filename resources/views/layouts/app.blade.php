<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Environment Manager')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'media' }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

<div class="min-h-full">
    {{-- Navigation --}}
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <div class="flex items-center gap-6">
                    <a href="{{ route('env-manager.index') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 tracking-wide uppercase">
                        ⚙ ENV Manager
                    </a>
                    <div class="hidden md:flex items-center gap-4 text-sm">
                        <a href="{{ route('env-manager.index') }}" class="nav-link {{ request()->routeIs('env-manager.index') ? 'active' : '' }}">Variables</a>
                        <a href="{{ route('env-manager.history') }}" class="nav-link {{ request()->routeIs('env-manager.history') ? 'active' : '' }}">History</a>
                        <a href="{{ route('env-manager.audit-log') }}" class="nav-link {{ request()->routeIs('env-manager.audit-log') ? 'active' : '' }}">Audit Log</a>
                        <a href="{{ route('env-manager.backups') }}" class="nav-link {{ request()->routeIs('env-manager.backups') ? 'active' : '' }}">Backups</a>
                        <a href="{{ route('env-manager.diff') }}" class="nav-link {{ request()->routeIs('env-manager.diff') ? 'active' : '' }}">Diff</a>
                        <a href="{{ route('env-manager.import-export') }}" class="nav-link {{ request()->routeIs('env-manager.import-export') ? 'active' : '' }}">Import/Export</a>
                    </div>
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500">
                    {{ config('app.env') }} · {{ config('app.name') }}
                </div>
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        @if(session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 px-4 py-3 text-sm text-green-800 dark:text-green-300 mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="rounded-md bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 px-4 py-3 text-sm text-red-800 dark:text-red-300 mb-4">
                @if(session('error'))
                    {{ session('error') }}
                @else
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                @endif
            </div>
        @endif
    </div>

    {{-- Page Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>
</div>

<style>
    .nav-link {
        @apply text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors;
    }
    .nav-link.active {
        @apply text-indigo-600 dark:text-indigo-400 font-medium;
    }
</style>

@stack('scripts')
</body>
</html>
