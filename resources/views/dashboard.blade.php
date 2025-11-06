@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-4">LLM Dashboard</h1>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <!-- Configured LLM APIs -->
    <div class="bg-white shadow-md rounded-lg p-4">
      <h2 class="text-xl font-semibold mb-2 flex items-center">
        Configured LLM APIs
      </h2>
      <ul class="list-disc list-inside">
        @foreach($availableApis as $apiName => $isConfigured)
        <li class="mb-2 flex items-center">
          @if($isConfigured)
          <img src="{{ asset('svg/' . $apiName . '-color.svg') }}" alt="{{ ucfirst($apiName) }} Logo" class="mr-2 h-6 w-6">
          <span>{{ ucfirst($apiName) }} API: <span class="font-medium text-green-600">Configured</span></span>
          @else
          <img src="{{ asset('svg/' . $apiName . '-color.svg') }}" alt="{{ ucfirst($apiName) }} Logo" class="mr-2 h-6 w-6 opacity-50">
          <span>{{ ucfirst($apiName) }} API: <span class="font-medium text-red-600">Not Configured</span></span>
          @endif
        </li>
        @endforeach
      </ul>
    </div>

    <!-- Available Prompts -->
    <div class="bg-white shadow-md rounded-lg p-4">
      <h2 class="text-xl font-semibold mb-2 flex items-center">
        Available Prompts
      </h2>
      <div class="bg-white shadow-md rounded-lg p-4">
        @if(false)
        {{-- Display prompts here --}}
        @else
        <p>No LLM packages found in composer.json.</p>
        @endif
        <a href="#" class="mt-4 inline-block bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
          Create New Prompt
        </a>
      </div>
    </div>

  </div>
</div>
@endsection
