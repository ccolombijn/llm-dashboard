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
        <script>
            // On page load or when changing themes, best to add inline in `head` to avoid FOUC
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
        {{-- Add the Vite directive --}}
        @vite(['resources/js/app.js','resources/css/app.css'])
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900" x-data="{
        theme: 'system',
        isDarkMode: false,
        setTheme(newTheme) {
            this.theme = newTheme
            localStorage.setItem('theme', newTheme)
            this.updateDarkMode()
        },
        updateDarkMode() {
            if (this.theme === 'dark') {
                this.isDarkMode = true
                document.documentElement.classList.add('dark')
            } else if (this.theme === 'light') {
                this.isDarkMode = false
                document.documentElement.classList.remove('dark')
            } else {
                this.isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches
                if (this.isDarkMode) {
                    document.documentElement.classList.add('dark')
                } else {
                    document.documentElement.classList.remove('dark')
                }
            }
        },
        init() {
            this.theme = localStorage.getItem('theme') || 'system'
            this.updateDarkMode()
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (this.theme === 'system') {
                    this.updateDarkMode()
                }
            })
        }
    }" x-init="init()">
        <div class="min-h-screen">
            @yield('content')
        </div>
    </body>
</html>
