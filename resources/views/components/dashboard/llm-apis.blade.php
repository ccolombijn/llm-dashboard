@props(['availableApis'])

<!-- Configured LLM APIs -->
<div class="bg-white shadow-md rounded-lg p-4 dark:bg-gray-800">
    <h2 class="text-xl font-semibold mb-2 flex items-center dark:text-gray-100">
        Configured LLM APIs
    </h2>
    <ul class="space-y-3">
        @foreach($availableApis as $apiName => $isConfigured)
        <li class="flex items-center justify-between p-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700" :class="isDefaultProvider('{{ $apiName }}') ? 'border-2 border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : ''">
            <div class="flex items-center">
            <img src="{{ asset('build/svg/' . $apiName . '.svg') }}" alt="{{ ucfirst($apiName) }} Logo" class="mr-3 h-6 w-6 @if(!$isConfigured) opacity-40 @endif" :class="{ 'filter-indigo-500': isDefaultProvider('{{ $apiName }}'), 'dark:invert': !isDefaultProvider('{{ $apiName }}') }">
            <span :class="{ 'text-indigo-500 dark:text-indigo-400 font-bold': isDefaultProvider('{{ $apiName }}'), 'dark:text-gray-300': !isDefaultProvider('{{ $apiName }}') }">{{ ucfirst($apiName) }} API:
                @if(!$isConfigured)
                         
                            <span class="font-medium text-sm text-red-600">Not Configured</span>
                        @endif
                    </span>
                </div>

                @if($isConfigured)
                    <div>
                        <template x-if="modelError['{{ $apiName }}']">
                            <div class="flex items-center space-x-2">
                                <span class="text-red-700 text-sm font-semibold"><i class="fa-solid fa-triangle-exclamation"></i> Could not fetch models</span>
                                <button @click="openConfigModal('{{ $apiName }}')"
                                        class="text-gray-500 py-1 px-3 rounded-md hover:bg-gray-700 text-sm font-semibold">
                                        <i class="fa-solid fa-gear"></i>
                                </button>
                                {{-- <button type="button" @click="openDeleteApiKeyModal('{{ $apiName }}')"
                                        class="bg-red-500 text-white py-1 px-3 rounded-md hover:bg-red-600 text-sm font-semibold">
                                        <i class="fa-solid fa-trash-can"></i>
                                </button> --}}
                            </div>
                        </template>
                        <template x-if="!modelError['{{ $apiName }}']">
                            <div class="flex items-center space-x-2">
                               
                                <button @click="openChangeModelModal('{{ $apiName }}')"
                                        :class="{
                                            'bg-indigo-500 hover:bg-indigo-600': isDefaultProvider('{{ $apiName }}'),
                                            'bg-gray-400 hover:bg-gray-500 dark:bg-gray-600 dark:hover:bg-gray-500': !isDefaultProvider('{{ $apiName }}')
                                        }"
                                        class="text-white py-1 px-3 rounded-md text-sm font-semibold">
                                        <span x-show="isDefaultProvider('{{ $apiName }}')" x-text="getDefaultModelName()" x-cloak></span>
                                        <span x-show="!isDefaultProvider('{{ $apiName }}')" x-cloak>Set Default</span>
                                </button>
                                <button @click="openTestModal('{{ $apiName }}')" class="text-gray-500 py-1 px-3 rounded-md hover:bg-gray-700 text-sm font-semibold">
                                    <i class="fa-solid fa-comment-nodes"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                @else
                    <a href="#" @click="getAPIKey('{{ $apiName }}')" class="-mr-52 bg-blue-500 text-white py-1 px-3 rounded-md hover:bg-blue-600 text-sm font-semibold"><i class="fa-solid fa-square-arrow-up-right"></i> Get API Key</a>
                    <button @click="openConfigModal('{{ $apiName }}')" class="bg-blue-500 text-white py-1 px-3 rounded-md hover:bg-blue-600 text-sm font-semibold">
                        <i class="fa-solid fa-gear"></i> Configure
                    </button>
                @endif
            </li>
        @endforeach
    </ul>
</div>
