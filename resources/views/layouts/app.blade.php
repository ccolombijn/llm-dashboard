<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <script>
            // On page load or when changing themes, best to add inline in `head` to avoid FOUC
            document.documentElement.setAttribute('data-theme',
                localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
                    ? 'dark'
                    : 'light');
        </script>
        {{-- Add the Vite directive --}}
        @vite(['resources/js/app.js','resources/css/app.css'])
    </head>
    @php
    $pageData = null;
    if (request()->routeIs('dashboard')) {
        $pageData = [
            'generateUrl' => route('ai.generate'),
            'modelsUrl' => route('ai.models'),
            'availableApis' => $availableApis ?? [],
        ];
    }
    @endphp
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
        <div class="min-h-screen">
            @yield('content')
        </div>
    </body>
</html>
