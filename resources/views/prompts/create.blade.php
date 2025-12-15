@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

<div class="container mx-auto p-4">
    <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h1 class="text-2xl font-bold dark:text-white mb-4">Create New Prompt</h1>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 dark:bg-red-900/30 dark:border-red-600 dark:text-red-300" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('prompts.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt Key</label>
                <input type="text" name="key" id="key" value="{{ old('key') }}" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                       placeholder="e.g., summarize_document">
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">A unique identifier for the prompt. Use snake_case (lowercase letters and underscores only).</p>
            </div>

            <div class="mb-6" x-data="promptConverter(@js(old('prompt', '')))">
                <div class="flex space-x-2 mb-2 border-b border-gray-200 dark:border-gray-700">
                    <button type="button" @click="switchTab('raw')"
                            :disabled="isLoading"
                            :class="activeTab === 'raw' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="px-4 py-2 font-medium text-sm focus:outline-none transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed">Raw</button>
                    <button type="button" @click="switchTab('json')"
                            :disabled="isLoading"
                            :class="activeTab === 'json' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="px-4 py-2 font-medium text-sm focus:outline-none transition-colors duration-150 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="activeTab === 'json' && isLoading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        JSON</button>
                    <button type="button" @click="switchTab('toon')"
                            :disabled="isLoading"
                            :class="activeTab === 'toon' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="px-4 py-2 font-medium text-sm focus:outline-none transition-colors duration-150 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="activeTab === 'toon' && isLoading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        TOON</button>
                    <button type="button" @click="switchTab('poml')"
                            :disabled="isLoading"
                            :class="activeTab === 'poml' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="px-4 py-2 font-medium text-sm focus:outline-none transition-colors duration-150 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="activeTab === 'poml' && isLoading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        POML</button>
                </div>

                <label for="prompt" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt Text</label>

                <!-- Editable Textarea for Raw Mode -->
                <textarea name="prompt" id="prompt" rows="8" required
                          x-show="activeTab === 'raw'"
                          x-model="displayPrompt"
                          @input="updatePrompt($event.target.value)"
                          class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                          placeholder="Enter the full prompt text here. You can use placeholders like :input."></textarea>

                <!-- Highlighted Code Display for Converted Modes -->
                <div class="relative" x-show="activeTab !== 'raw'">
                    <button type="button"
                            @click="copyToClipboard()"
                            class="absolute top-2 right-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs py-1 px-2 rounded dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300 transition-colors z-10"
                            x-text="copyButtonText">
                    </button>
                    <pre class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm overflow-auto h-64 dark:bg-gray-900 dark:border-gray-600"><code class="text-sm bg-transparent hljs" x-html="highlightedCode"></code></pre>
                </div>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">The full text of the prompt. You can include placeholders like <code>:input</code> which can be replaced at runtime.</p>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('dashboard') }}" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 font-semibold">Save Prompt</button>
            </div>
        </form>
    </div>
</div>
@endsection
