<!-- Configuration Modal -->
<div x-show="activeModal === 'config'" x-cloak @keydown.escape.window="activeModal = null" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
    <div @click.away="activeModal = null" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md dark:bg-gray-800">
       <h3 class="text-lg font-bold mb-4 dark:text-white">Configure <span x-text="modalProvider.charAt(0).toUpperCase() + modalProvider.slice(1)"></span> API</h3>
        <form action="{{ route('dashboard.update-api-key') }}" method="POST">
            @csrf
            <input type="hidden" name="provider" :value="modalProvider">
            <div>
                <label for="api_key_input" class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Key</label>
                <input type="password" id="api_key_input" name="api_key" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" x-ref="apiKeyInput">
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" @click="activeModal = null" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Cancel</button>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 font-semibold">Save Key</button>
            </div>
        </form>
    </div>
</div>

