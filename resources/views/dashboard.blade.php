@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4" x-data="Object.assign(dashboard('{!! route('ai.generate') !!}', '{!! route('ai.models') !!}', {{ Js::from($availableApis) }}), { selectedProvider: '' })">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold dark:text-white">LLM Dashboard</h1>
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <span class="sr-only">Open theme options</span>
                <svg x-show="theme === 'light'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <svg x-show="theme === 'dark'" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                <svg x-show="theme === 'system'" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </button>
            <div x-show="open" @click.away="open = false" x-cloak class="origin-top-right absolute right-0 mt-2 w-36 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" tabindex="-1">
                <div class="py-1" role="none">
                    <a href="#" @click.prevent="setTheme('light'); open = false" class="flex items-center gap-2 text-gray-700 dark:text-gray-200 block px-4 py-2 text-sm" :class="{ 'bg-gray-100 dark:bg-gray-700': theme === 'light' }" role="menuitem" tabindex="-1">Light</a>
                    <a href="#" @click.prevent="setTheme('dark'); open = false" class="flex items-center gap-2 text-gray-700 dark:text-gray-200 block px-4 py-2 text-sm" :class="{ 'bg-gray-100 dark:bg-gray-700': theme === 'dark' }" role="menuitem" tabindex="-1">Dark</a>
                    <a href="#" @click.prevent="setTheme('system'); open = false" class="flex items-center gap-2 text-gray-700 dark:text-gray-200 block px-4 py-2 text-sm" :class="{ 'bg-gray-100 dark:bg-gray-700': theme === 'system' }" role="menuitem" tabindex="-1">System</a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 dark:bg-green-900/30 dark:border-green-600 dark:text-green-300" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Configured LLM APIs -->
        <div class="bg-white shadow-md rounded-lg p-4 dark:bg-gray-800">
            <h2 class="text-xl font-semibold mb-2 flex items-center dark:text-gray-100">
                Configured LLM APIs
            </h2>
            <ul class="space-y-3">
                @foreach($availableApis as $apiName => $isConfigured)
                    <li class="flex items-center justify-between p-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex items-center">
                            <img src="{{ asset('build/svg/' . $apiName . '.svg') }}" alt="{{ ucfirst($apiName) }} Logo" class="mr-3 h-6 w-6 @if(!$isConfigured) opacity-40 @endif">
                            <span class="dark:text-gray-300">{{ ucfirst($apiName) }} API:
                                @if($isConfigured)
                                    <span class="font-medium text-green-600">Configured</span>
                                @else
                                    <span class="font-medium text-red-600">Not Configured</span>
                                @endif
                            </span>
                        </div>

                        @if($isConfigured)
                            <button @click="openTestModal('{{ $apiName }}')" class="bg-green-500 text-white py-1 px-3 rounded-md hover:bg-green-600 text-sm font-semibold">
                                Test
                            </button>
                        @else
                            <a href="#" @click="getAPIKey('{{ $apiName }}')" class="-mr-56 bg-blue-500 text-white py-1 px-3 rounded-md hover:bg-blue-600 text-sm font-semibold">Get API Key</a>
                            <button @click="openConfigModal('{{ $apiName }}')" class="bg-blue-500 text-white py-1 px-3 rounded-md hover:bg-blue-600 text-sm font-semibold">
                                Configure
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Available Prompts -->
        <div class="bg-white shadow-md rounded-lg p-4 dark:bg-gray-800">
            <h2 class="text-xl font-semibold mb-2 flex items-center dark:text-gray-100">
                Available Prompts
            </h2>
            @if(empty($prompts))
                <p class="dark:text-gray-300">No prompts available.</p>
            @else
                <ul class="space-y-2">
                    @foreach($prompts as $key => $prompt)
                        <li class="flex items-center justify-between p-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                            <div class="w-[85%]">
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>
                                <p class="text-sm text-gray-600 truncate dark:text-gray-400"><code>{{ Str::limit($prompt, 100) }}</code></p>
                            </div>
                            <button type="button" @click="openTestModal(null, '{{ $key }}')" class="bg-green-500 text-white py-1 px-3 rounded-md hover:bg-green-600 text-sm font-semibold ml-2">
                                Test
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
            <a href="#" class="mt-4 inline-block bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                Create New Prompt
            </a>
        </div>
    </div>

    <!-- Global Modals Container -->
    <!-- Configuration Modal -->
    <template x-teleport="body">
        <div>
            <div x-show="isConfigModalOpen" x-cloak @keydown.escape.window="isConfigModalOpen = false" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
                <div @click.away="isConfigModalOpen = false" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md dark:bg-gray-800">
                    <h3 class="text-lg font-bold mb-4 dark:text-white">Configure <span x-text="modalProvider.charAt(0).toUpperCase() + modalProvider.slice(1)"></span> API</h3>
                    <form action="{{ route('dashboard.update-api-key') }}" method="POST">
                        @csrf
                        <input type="hidden" name="provider" :value="modalProvider">
                        <div>
                            <label for="api_key_input" class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key</label>
                            <input type="password" id="api_key_input" name="api_key" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" x-ref="apiKeyInput">
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="isConfigModalOpen = false" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Cancel</button>
                            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 font-semibold">Save Key</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Test Modal -->
            <div x-show="isTestModalOpen" x-cloak @keydown.escape.window="isTestModalOpen = false" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
                <div @click.away="isTestModalOpen = false" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md dark:bg-gray-800">
                    <h3 class="text-lg font-bold mb-4 dark:text-white">Test <span x-text="modalProvider.charAt(0).toUpperCase() + modalProvider.slice(1)"></span> API</h3>
                    <form @submit.prevent="runTest()">
                        <div>
                            <label for="test_prompt_input" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt</label>
                            <textarea id="test_prompt_input" x-model="testPrompt" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" x-ref="testPromptInput" rows="3"></textarea>
                        </div>
                        <div class="mt-4">
                            <label for="test_provider_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Provider</label>
                            <select id="test_provider_select" x-model="selectedProvider" @change="modalProvider = selectedProvider" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <template x-for="(isConfigured, apiName) in availableApis" :key="apiName">
                                    <option x-bind:value="apiName" x-text="apiName.charAt(0).toUpperCase() + apiName.slice(1)" x-show="isConfigured"></option>
                                </template>
                            </select>
                        </div>
                        <div x-show="testPrompt === 'explanation' || testPrompt === 'summarize'" class="mt-4">
                            <label for="test_input_field" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Input</label>
                            <input type="text" id="test_input_field" x-model="testInput" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="isTestModalOpen = false" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Close</button>
                            <button type="submit" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 font-semibold flex items-center" :disabled="isTesting">
                                <svg x-show="isTesting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isTesting ? 'Sending...' : 'Send Request'"></span>
                            </button>
                        </div>
                    </form>

                    <div x-show="testResponse" class="mt-4 p-4 bg-gray-100 rounded-md max-h-60 overflow-y-auto dark:bg-gray-700/50">
                        <h4 class="font-semibold text-gray-800 mb-2 dark:text-gray-200">Response:</h4>
                        <pre class="text-sm text-gray-700 whitespace-pre-wrap dark:text-gray-300" x-text="testResponse"></pre>
                    </div>

                    <div x-show="testResponseDetails" class="mt-4 text-xs text-gray-500 grid grid-cols-2 gap-x-4 gap-y-2">
                        <div class="flex justify-between border-b pb-1 dark:border-gray-700">                            <span>Input Tokens:</span><span class="font-mono" x-text="testResponseDetails?.tokensIn"></span></div><div class="flex justify-between border-b pb-1 dark:border-gray-700">

                        <div class="flex justify-between border-b pb-1 dark:border-gray-700">
                            <span>Output Tokens:</span><span class="font-mono" x-text="testResponseDetails?.tokensOut"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1 dark:border-gray-700"><span>Response Time:</span><span class="font-mono" x-text="`${testResponseDetails?.time}s`"></span></div>
                        <div class="flex justify-between border-b pb-1 dark:border-gray-700"><span>Size:</span><span class="font-mono" x-text="testResponseDetails?.bytes"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
