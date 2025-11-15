<!-- Test Modal -->
<div x-show="activeModal === 'test'" x-cloak @keydown.escape.window="activeModal = null" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
    <div @click.away="activeModal = null" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md dark:bg-gray-800">
        <h3 class="text-lg font-bold mb-4 dark:text-white"><i class="fa-solid fa-comment-nodes"></i> Test <span x-text="modalProvider.charAt(0).toUpperCase() + modalProvider.slice(1)"></span> API</h3>
        <form @submit.prevent="runTest()">
            <div>
                <label for="test_prompt_input" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt</label>
                <textarea id="test_prompt_input" x-model="testPrompt" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" x-ref="testPromptInput" rows="3"></textarea>
            </div>
            <div x-show="!isApiTest" class="mt-4">
                <label for="test_provider_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Provider</label>
                <select id="test_provider_select" x-model="selectedProvider" @change="modalProvider = selectedProvider" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <template x-for="(isConfigured, apiName) in availableApis" :key="apiName">
                        <option x-bind:value="apiName" x-text="apiName.charAt(0).toUpperCase() + apiName.slice(1)" x-show="isConfigured && !modelError[apiName]"></option>
                    </template>
                </select>
            </div>
            <div class="mt-4">
                <label for="test_model_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model (Optional)</label>
                <select id="test_model_select" x-model="selectedModel" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Provider Default <span x-show="providerDefaultModel" x-text="`(${providerDefaultModel})`"></span></option>
                    <template x-if="availableModels.models && availableModels.models[selectedProvider]">
                        <template x-for="model in availableModels.models[selectedProvider]" :key="model.id">
                            <option :value="model.id" x-text="model.id"></option>
                        </template>
                    </template>
                </select>
            </div>
            <div x-show="testPrompt === 'explanation' || testPrompt === 'summarize'" class="mt-4">
                <label for="test_input_field" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Input</label>
                <input type="text" id="test_input_field" x-model="testInput" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" @click="activeModal = null" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Close</button>
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
            <div class="flex justify-between border-b pb-1 dark:border-gray-700">                            
                <span>Input Tokens:</span><span class="font-mono" x-text="testResponseDetails?.tokensIn"></span>
            </div><div class="flex justify-between border-b pb-1 dark:border-gray-700">

            <div class="flex justify-between border-b pb-1 dark:border-gray-700">
                <span>Output Tokens:</span><span class="font-mono" x-text="testResponseDetails?.tokensOut"></span>
            </div>
            <div class="flex justify-between border-b pb-1 dark:border-gray-700">
                <span>Response Time:</span><span class="font-mono" x-text="`${testResponseDetails?.time}s`"></span>
            </div>
            <div class="flex justify-between border-b pb-1 dark:border-gray-700">
                <span>Size:</span><span class="font-mono" x-text="testResponseDetails?.bytes"></span>
            </div>
        </div>
    </div>
</div>
