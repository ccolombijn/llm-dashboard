<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('AI Profiles') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 lg:p-8 bg-white dark:bg-gray-800 dark:bg-gradient-to-bl dark:from-gray-700/50 dark:via-transparent border-b border-gray-200 dark:border-gray-700">

                    <!-- Add Profile Form -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Add New Profile</h3>
                        <form action="{{ route('admin.profiles.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <x-label for="name" value="{{ __('Profile Name') }}" />
                                <x-input id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="off" />
                                <x-input-error for="name" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="system_prompt" value="{{ __('System Prompt') }}" />
                                <textarea id="system_prompt" name="system_prompt" rows="4" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mt-1 block w-full"></textarea>
                                <x-input-error for="system_prompt" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end">
                                <x-button>
                                    {{ __('Add Profile') }}
                                </x-button>
                            </div>
                        </form>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-700 my-8">

                    <!-- Existing Profiles List -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Existing Profiles</h3>
                        <div class="space-y-4">
                            @forelse ($profiles as $profile)
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200">{{ $profile['name'] }}</h4>
                                    @if(!empty($profile['system_prompt']))
                                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                            <strong>System Prompt:</strong> {{ Str::limit($profile['system_prompt'], 150) }}
                                        </p>
                                    @endif
                                    {{-- TODO: Add edit and delete buttons --}}
                                </div>
                            @empty
                                <p class="text-gray-500 dark:text-gray-400">No profiles found.</p>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
