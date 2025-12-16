@props([
    'name' => 'prompt',
    'label' => 'Prompt',
    'value' => '',
    'helpText' => '',
])

<div x-data="promptConverter(@js($value))" {{ $attributes->only('class')->merge(['class' => 'mb-6']) }}>
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

    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    <textarea name="{{ $name }}" id="{{ $name }}"
              x-show="activeTab === 'raw'"
              x-model="displayPrompt"
              @input="updatePrompt($event.target.value)"
              {{ $attributes->except('class') }}
              class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-gray-900 dark:text-gray-100"></textarea>

    <div class="relative" x-show="activeTab !== 'raw'">
        <div class="absolute top-2 right-2 flex items-center space-x-2 z-10">
            <div class="text-xs text-gray-500 dark:text-gray-400 bg-white/80 dark:bg-gray-800/80 px-2 py-1 rounded backdrop-blur-sm border border-gray-200 dark:border-gray-700" x-show="stats.size > 0">
                <span x-text="stats.size + ' chars'"></span>
                <span x-show="stats.tokens > 0" x-text="' • ' + stats.tokens + ' tokens'"></span>
            </div>
            <button type="button"
                    @click="convertContent(true)"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs py-1 px-2 rounded dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300 transition-colors"
                    title="Refresh conversion">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
            </button>
            <button type="button"
                    @click="copyToClipboard()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs py-1 px-2 rounded dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300 transition-colors"
                    x-text="copyButtonText">
            </button>
        </div>
        <pre class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm overflow-auto h-64 dark:bg-gray-900 dark:border-gray-600 whitespace-pre-wrap"><code class="text-sm bg-transparent hljs" x-html="highlightedCode"></code></pre>
    </div>

    @if($helpText)
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{!! $helpText !!}</p>
    @endif
</div>
