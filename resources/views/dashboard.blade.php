@extends('layouts.app')

@section('content')
<style>
    .filter-indigo-500 {
        filter: invert(39%) sepia(57%) saturate(2558%) hue-rotate(215deg) brightness(98%) contrast(92%);
    }
</style>
<div class="container mx-auto p-4" x-data="dashboard(
        '{{ route('dashboard.update-default-handler') }}',
        '{{ route('ai.generate') }}',
        '{{ route('ai.models') }}',
        {{ json_encode($availableApis) }},
        '{{ config('ai.default_handler') }}'
    )">
    <div class="flex justify-between items-center mb-4">
        <!-- Success Notification -->
        <div
            x-show="showSuccessNotification"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="fixed top-5 right-5 bg-green-500 text-white py-2 px-4 rounded-lg shadow-md z-50"
            x-text="successNotificationMessage"
            x-cloak>
        </div>
        <h1 class="text-2xl font-bold dark:text-white">LLM Dashboard</h1>
        <div class="relative z-50">
            <x-dashboard.theme-switcher />
        </div>
    </div>

    @if (session('success'))
        <div x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 10000)"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 dark:bg-green-900/30 dark:border-green-600 dark:text-green-300"
        role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Configured LLM APIs -->
        <x-dashboard.llm-apis :available-apis="$availableApis" />
        <!-- Available Prompts -->
        <x-dashboard.prompts :prompts="$prompts" :custom-prompt-keys="$customPromptKeys ?? []" />
    </div>

    <!-- Global Modals Container -->
    
    <template x-teleport="body">
        <div>
            <x-dashboard.modals.config />
            <x-dashboard.modals.change-model />
            <x-dashboard.modals.test />
            <x-dashboard.modals.delete-prompt />
            <x-dashboard.modals.delete-api-key />
        </div>
    </template>
</div>
@endsection
