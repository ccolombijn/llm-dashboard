@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">Create New AI Profile</h1>

        <form action="{{ route('profiles.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Name</label>
                <input type="text" name="name" id="name" required
                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       value="{{ old('name') }}">
                @error('name')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="system_prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300">System Prompt</label>
                <textarea name="system_prompt" id="system_prompt" rows="6"
                          class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('system_prompt') }}</textarea>
                @error('system_prompt')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out">
                    Save Profile
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
