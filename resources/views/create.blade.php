@extends('environment-manager::layouts.app')

@section('title', 'Add Variable')

@section('content')
<div class="max-w-xl">
    <h1 class="text-xl font-semibold mb-6">Add Variable</h1>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <form method="POST" action="{{ route('env-manager.store') }}">
            @csrf
            @include('environment-manager::partials.variable-form', ['variable' => null])
        </form>
    </div>
</div>
@endsection
