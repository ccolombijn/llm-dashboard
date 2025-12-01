<div x-show="activeModal === 'deleteProfile'" x-cloak class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 px-4">
    <div @click.away="activeModal = null" class="bg-white dark:bg-gray-900 rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Confirm Profile Deletion</h3>
        <p class="mt-2 text-gray-600 dark:text-gray-300">
            Are you sure you want to delete the profile "<strong class="font-semibold" x-text="profileToDeleteName"></strong>"? This action cannot be undone.
        </p>
        <div class="mt-6 flex justify-end space-x-4">
            <button @click="activeModal = null" type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 transition-colors">
                Cancel
            </button>
            <button @click="document.getElementById(formToDeleteId).submit()" type="button" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                Delete Profile
            </button>
        </div>
    </div>
</div>
