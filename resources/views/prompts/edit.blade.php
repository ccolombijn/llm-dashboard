@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h1 class="text-2xl font-bold dark:text-white mb-4">Edit Prompt</h1>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 dark:bg-red-900/30 dark:border-red-600 dark:text-red-300" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('prompts.update', $key) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label for="key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt Key</label>
                <input type="text" name="key" id="key" value="{{ $key }}" readonly
                       class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">The prompt key cannot be changed.</p>
            </div>

            <div class="mb-6">
                <label for="prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt Text</label>
                <textarea name="prompt" id="prompt" rows="8" required
                          class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">{{ old('prompt', $prompt) }}</textarea>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">The full text of the prompt. You can include placeholders like <code>:input</code> which can be replaced at runtime.</p>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('dashboard') }}" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 font-semibold">Update Prompt</button>
            </div>
        </form>
    </div>
</div>
@endsection
