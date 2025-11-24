@props(['profiles'])

<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold dark:text-white">Available Profiles</h2>
        <a href="{{ route('profiles.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out">
            Add Profile
        </a>
    </div>
    <div class="space-y-2">
        @forelse ($profiles as $profile)
            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                <div>
                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $profile['name'] }}</span>
                    @if(!empty($profile['system_prompt']))
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ Str::limit($profile['system_prompt'], 60) }}
                        </p>
                    @endif
                </div>
                <div class="flex items-center space-x-2">
                    {{-- <a href="#" class="text-blue-500 hover:text-blue-700">Edit</a>
                    <button @click="openDeleteModal('{{ $profile['name'] }}')" class="text-red-500 hover:text-red-700">Delete</button> --}}
                </div>
            </div>
        @empty
            <p class="text-gray-500 dark:text-gray-400">No custom profiles found.</p>
        @endforelse
    </div>
</div>
