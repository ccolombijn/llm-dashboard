<!-- Change Model Modal -->
<div x-show="activeModal === 'changeModel'" x-cloak @keydown.escape.window="activeModal = null" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 p-4">
    <div @click.away="activeModal = null" class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md dark:bg-gray-800">
       <h3 class="text-lg font-bold mb-4 dark:text-white">Change Default Model for <span x-text="modalProvider.charAt(0).toUpperCase() + modalProvider.slice(1)"></span></h3>
       <form action="{{ route('dashboard.update-default-handler') }}" method="POST">
        @csrf
        <input type="hidden" name="provider" :value="modalProvider">
        <div>
            <label for="default_model_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
            <select name="model" id="default_model_select" x-model="newDefaultModel" x-ref="defaultModelSelect" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="" disabled>-- Select a model --</option>
                <template x-if="availableModels.models && availableModels.models[modalProvider]">
                    <template x-for="model in availableModels.models[modalProvider]" :key="model.id">
                        <option :value="model.id" x-text="model.id"></option>
                    </template>
                </template>
            </select>
        </div>
        <div class="mt-6 flex justify-end space-x-3">
            <button type="button" @click="activeModal = null" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Cancel</button>
            <button type="submit" class="text-white py-2 px-4 rounded-md bg-indigo-500 hover:bg-indigo-600' font-semibold">Set as Default</button>
        </div>
    </form>
    </div>
</div>
