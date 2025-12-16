@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">
            Create Profile
        </h1>

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            {{-- We'll point this to a real route in the next step --}}
            <form action="{{ route('profiles.store') }}" method="POST" class="space-y-6">
                @csrf

                {{-- Profile Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Name</label>
                    <input type="text" name="name" id="name" required
                           class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-gray-900 dark:text-gray-100"
                           placeholder="e.g., Content Summarizer">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">A descriptive name for the profile. This will be used as the filename.</p>
                </div>

                {{-- Prompt --}}
                <x-prompt-editor
                    name="system_prompt"
                    label="Prompt"
                    :value="old('system_prompt', '')"
                    placeholder="You are a helpful assistant. Summarize the following content..."
                    help-text="The main prompt or instruction for the AI model."
                    required
                    rows="8"
                />

                {{-- Context Files --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Context Files</label>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Select files to include in the context of the prompt.</p>
                    <div class="mt-2 max-h-60 overflow-y-auto rounded-md border border-gray-300 dark:border-gray-600 p-2 space-y-2">
                        @forelse ($files as $file)
                            <div class="flex items-center">
                                <input id="file_{{ $loop->index }}" name="files[]" type="checkbox" value="{{ $file }}"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-600">
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
                        Create Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
