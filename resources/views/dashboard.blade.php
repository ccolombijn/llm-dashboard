@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4" x-data="dashboard('{!! route('ai.generate') !!}', '{!! route('ai.models') !!}', {{ Js::from($availableApis) }})">
    <h1 class="text-2xl font-bold mb-4">LLM Dashboard</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Configured LLM APIs -->
        <div class="bg-white shadow-md rounded-lg p-4">
            <h2 class="text-xl font-semibold mb-2 flex items-center">
                Configured LLM APIs
            </h2>
            <ul class="space-y-3">
                @foreach($availableApis as $apiName => $isConfigured)
                    <li class="flex items-center justify-between p-2 rounded-md hover:bg-gray-50">
                        <div class="flex items-center">
                            <img src="{{ asset('build/svg/' . $apiName . '.svg') }}" alt="{{ ucfirst($apiName) }} Logo" class="mr-3 h-6 w-6 @if(!$isConfigured) opacity-40 @endif">
                            <span>{{ ucfirst($apiName) }} API:
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
                            <button @click="openConfigModal('{{ $apiName }}')" class="bg-blue-500 text-white py-1 px-3 rounded-md hover:bg-blue-600 text-sm font-semibold">
                                Configure
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Available Prompts -->
        <div class="bg-white shadow-md rounded-lg p-4">
            <h2 class="text-xl font-semibold mb-2 flex items-center">
                Available Prompts
            </h2>
            @if(empty($prompts))
                <p>No prompts available.</p>
            @else
                <ul class="space-y-2">
                    @foreach($prompts as $key => $prompt)
                        <li class="flex items-center justify-between p-2 rounded-md hover:bg-gray-50">
                            <div>
                                <p class="font-semibold text-gray-800">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>
                                <p class="text-sm text-gray-600 truncate"><code>{{ Str::limit($prompt, 100) }}</code></p>
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
                <div @click.away="isConfigModalOpen = false" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md">
                    <h3 class="text-lg font-bold mb-4">Configure <span x-text="modalProvider.charAt(0).toUpperCase() + modalProvider.slice(1)"></span> API</h3>
                    <form action="{{ route('dashboard.update-api-key') }}" method="POST">
                        @csrf
                        <input type="hidden" name="provider" :value="modalProvider">
                        <div>
                            <label for="api_key_input" class="block text-sm font-medium text-gray-700">API Key</label>
                            <input type="password" id="api_key_input" name="api_key" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" x-ref="apiKeyInput">
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="isConfigModalOpen = false" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold">Cancel</button>
                            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 font-semibold">Save Key</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Test Modal -->
            <div x-show="isTestModalOpen" x-cloak @keydown.escape.window="isTestModalOpen = false" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
                <div @click.away="isTestModalOpen = false" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md">
                    <h3 class="text-lg font-bold mb-4">Test <span x-text="modalProvider.charAt(0).toUpperCase() + modalProvider.slice(1)"></span> API</h3>
                    <form @submit.prevent="runTest()">
                        <div>
                            <label for="test_prompt_input" class="block text-sm font-medium text-gray-700">Prompt</label>
                            <textarea id="test_prompt_input" x-model="testPrompt" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" x-ref="testPromptInput" rows="3"></textarea>
                        </div>
                        <div class="mt-4">
                            <label for="test_provider_select" class="block text-sm font-medium text-gray-700">Provider</label>
                            <select id="test_provider_select" x-model="selectedProvider" @change="modalProvider = selectedProvider" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <template x-for="(isConfigured, apiName) in availableApis" :key="apiName">
                                    <option x-bind:value="apiName" x-text="apiName.charAt(0).toUpperCase() + apiName.slice(1)" x-show="isConfigured"></option>
                                </template>
                            </select>
                        </div>
                        <div x-show="testPrompt === 'explanation' || testPrompt === 'summarize'" class="mt-4">
                            <label for="test_input_field" class="block text-sm font-medium text-gray-700">Input</label>
                            <input type="text" id="test_input_field" x-model="testInput" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="isTestModalOpen = false" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold">Close</button>
                            <button type="submit" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 font-semibold flex items-center" :disabled="isTesting">
                                <svg x-show="isTesting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isTesting ? 'Sending...' : 'Send Request'"></span>
                            </button>
                        </div>
                    </form>

                    <div x-show="testResponse" class="mt-4 p-4 bg-gray-100 rounded-md max-h-60 overflow-y-auto">
                        <h4 class="font-semibold text-gray-800 mb-2">Response:</h4>
                        <pre class="text-sm text-gray-700 whitespace-pre-wrap" x-text="testResponse"></pre>
                    </div>

                    <div x-show="testResponseDetails" class="mt-4 text-xs text-gray-500 grid grid-cols-2 gap-x-4 gap-y-2">
                        <div class="flex justify-between border-b pb-1">
                            <span>Input Tokens:</span><span class="font-mono" x-text="testResponseDetails?.tokensIn"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1">
                            <span>Output Tokens:</span><span class="font-mono" x-text="testResponseDetails?.tokensOut"></span>
                        </div>
                        <div class="flex justify-between border-b pb-1"><span>Response Time:</span><span class="font-mono" x-text="`${testResponseDetails?.time}s`"></span></div>
                        <div class="flex justify-between border-b pb-1"><span>Size:</span><span class="font-mono" x-text="testResponseDetails?.bytes"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
