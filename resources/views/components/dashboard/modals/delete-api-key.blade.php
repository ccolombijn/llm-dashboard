<!-- Delete API Key Confirmation Modal -->
<div x-show="activeModal === 'deleteApiKey'" x-cloak @keydown.escape.window="activeModal = null" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
    <div @click.away="activeModal = null" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md dark:bg-gray-800">
        <h3 class="text-lg font-bold mb-4 dark:text-white">Delete API Key</h3>
        <p class="mb-6 dark:text-gray-300">Are you sure you want to delete the API key for "<span x-text="providerToDeleteKey.charAt(0).toUpperCase() + providerToDeleteKey.slice(1)" class="font-semibold"></span>"? This action cannot be undone and will set the provider to 'Not Configured'.</p>

        <form :action="`/dashboard/api-key/${providerToDeleteKey}`" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex justify-end space-x-3">
                <button type="button" @click="activeModal = null" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Cancel</button>
                <button type="submit" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 font-semibold">Delete API Key</button>
            </div>
        </form>
    </div>
</div>