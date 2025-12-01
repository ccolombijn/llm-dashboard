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
                  <a href="{{ route('profiles.edit', ['profile' => strtolower($profile['name'])]) }}" class="text-gray-500 py-1 px-3 rounded-md hover:bg-gray-600 text-sm font-semibold"><i class="fa-solid fa-pen"></i></a>
                    <form action="{{ route('profiles.destroy', ['profile' => strtolower($profile['name'])]) }}" method="POST" id="delete-profile-form-{{ $loop->index }}">
                        @csrf
                        @method('DELETE')
                    </form>
                    <button type="button"
                            class="text-gray-500 py-1 px-3 rounded-md hover:bg-gray-700 text-sm font-semibold"
                            @click="openDeleteProfileModal('delete-profile-form-{{ $loop->index }}', '{{ addslashes($profile['name']) }}')">
                        <i class="fa-solid fa-trash-can"></i></button>
                </div>

            </div>
        @empty
            <p class="text-gray-500 dark:text-gray-400">No custom profiles found.</p>
        @endforelse
    </div>
</div>
