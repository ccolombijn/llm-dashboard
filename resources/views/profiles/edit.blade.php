@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">
            Edit Profile: {{ $profile['name'] }}
        </h1>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <form action="{{ route('profiles.update', $profile['name']) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Profile Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Name</label>
                    <input type="text" name="name" id="name" required value="{{ old('name', $profile['name']) }}"
                           class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-gray-900 dark:text-gray-100"
                           placeholder="e.g., Content Summarizer">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">A descriptive name for the profile. This will be used as the filename.</p>
                </div>

                {{-- Prompt --}}
                <div>
                    <label for="system_prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt</label>
                    <textarea name="system_prompt" id="system_prompt" rows="8" required
                              class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-gray-900 dark:text-gray-100"
                              placeholder="You are a helpful assistant. Summarize the following content...">{{ old('system_prompt', $profile['system_prompt']) }}</textarea>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">The main prompt or instruction for the AI model.</p>
                </div>

                {{-- Context Files --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Context Files</label>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Select files to include in the context of the prompt.</p>
                    <div class="mt-2 max-h-60 overflow-y-auto rounded-md border border-gray-300 dark:border-gray-600 p-2 space-y-2">
                        @php
                            $selectedFiles = old('files', $profile['files'] ?? []);
                        @endphp
                        @forelse ($files as $file)
                            <div class="flex items-center">
                                <input id="file_{{ $loop->index }}" name="files[]" type="checkbox" value="{{ $file }}"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-600"
                                       @if(in_array($file, $selectedFiles)) checked @endif>
                                <label for="file_{{ $loop->index }}" class="ml-3 block text-sm text-gray-900 dark:text-gray-200">
                                    {{ basename($file) }}
                                </label>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No files available for selection.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
