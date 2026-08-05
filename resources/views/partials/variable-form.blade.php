<div class="space-y-4">
    {{-- Key --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Key</label>
        @if($variable)
            <input type="text" value="{{ $variable->key }}" disabled
                   class="w-full border border-gray-200 dark:border-gray-600 rounded-md px-3 py-2 text-sm font-mono bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
        @else
            <input type="text" name="key" value="{{ old('key') }}"
                   placeholder="APP_MY_VARIABLE"
                   pattern="[A-Z_][A-Z0-9_]*"
                   class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('key') border-red-500 @enderror"
                   required>
            <p class="text-xs text-gray-400 mt-1">Must be UPPER_SNAKE_CASE (e.g. APP_DEBUG)</p>
            @error('key')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        @endif
    </div>

    {{-- Value --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Value</label>
        <input type="{{ $variable?->sensitive ? 'password' : 'text' }}"
               name="value"
               value="{{ old('value', $variable?->rawValue) }}"
               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('value') border-red-500 @enderror"
               autocomplete="off">
        @if($variable?->sensitive)
            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">⚠ This is a sensitive variable. Value is masked for display.</p>
        @endif
        @error('value')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Reason --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Reason <span class="text-gray-400 font-normal">(optional)</span>
        </label>
        <input type="text" name="reason" value="{{ old('reason') }}"
               placeholder="Why is this change being made?"
               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3 pt-2">
        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-md transition-colors">
            {{ $variable ? 'Update' : 'Save' }}
        </button>
        <a href="{{ route('env-manager.index') }}"
           class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
            Cancel
        </a>
    </div>
</div>
