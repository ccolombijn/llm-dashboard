@props(['prompts', 'customPromptKeys' => []])

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
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('prompts.edit', ['key' => $key]) }}" class="text-gray-500 py-1 px-3 rounded-md hover:bg-gray-600 text-sm font-semibold">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        @if(in_array($key, $customPromptKeys))
                        <button type="button" @click="openDeletePromptModal('{{ $key }}')" class="bg-red-500 text-white py-1 px-3 rounded-md hover:bg-red-600 text-sm font-semibold">
                            Delete
                        </button>
                        @endif
                        <button type="button" @click="openTestModal(null, '{{ $key }}')" class="text-gray-500 py-1 px-3 rounded-md hover:bg-gray-600 text-sm font-semibold">
                            <i class="fa-solid fa-comment-nodes"></i>
                        </button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
    <a href="{{ route('prompts.create') }}" class="mt-4 inline-block tex-sm font-semibold text-center text-white  bg-blue-500 py-2 px-4 rounded hover:bg-blue-600">
        <i class="fa-solid fa-plus"></i> Create New Prompt
    </a>
</div>

