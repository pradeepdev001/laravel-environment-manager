@extends('environment-manager::layouts.app')

@section('title', 'Edit Variable')

@section('content')
<div class="max-w-xl">
    <h1 class="text-xl font-semibold mb-6">Edit <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded text-indigo-600 dark:text-indigo-400">{{ $variable->key }}</code></h1>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <form method="POST" action="{{ route('env-manager.update', $variable->key) }}">
            @csrf
            @method('PUT')
            @include('environment-manager::partials.variable-form', ['variable' => $variable])
        </form>
    </div>
</div>
@endsection
