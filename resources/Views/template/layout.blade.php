<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary Meta Tags -->
    <title>{{ $title }}</title>
    <meta name="description"
        content="THE FRAMEWORK: High-performance PHP framework with Laravel-like features including database migrations, seeders, RESTful API support, and elegant syntax. Perfect for modern web development.">
    <meta name="keywords"
        content="PHP Framework, MVC, Database Migrations, REST API, Web Development, Laravel Alternative, MySQL, Composer">
    <meta name="author" content="Chandra Tri Antomo">
    <meta name="robots" content="index, follow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://kiwkiw-mvc.tech/">
    <meta property="og:title" content="THE FRAMEWORK - Modern PHP Framework for Web Artisans">
    <meta property="og:description"
        content="Build scalable web applications with THE FRAMEWORK framework featuring database migrations, REST API support, and enterprise-grade security">
    <meta property="og:image" content="https://kiwkiw-mvc.tech/images/og-banner.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://kiwkiw-mvc.tech/">
    <meta property="twitter:title" content="THE FRAMEWORK - Modern PHP Framework for Web Artisans">
    <meta property="twitter:description"
        content="High-performance PHP framework with Laravel-like features and minimalist architecture">
    <meta property="twitter:image" content="https://kiwkiw-mvc.tech/images/og-banner.jpg">

    <!-- Canonical -->
    <link rel="canonical" href="https://kiwkiw-mvc.tech/">

    <!-- Favicon -->
    <link rel="icon" href="{{ url('/file/public/favicon.ico') }}">

    <!-- Preload Resources -->
    <link rel="preload" href="https://cdn.tailwindcss.com" as="script">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://unpkg.com">

    <!-- Stylesheets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>

    <style>
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: rgb(17 24 39 / 0.8);
        }

        ::-webkit-scrollbar-thumb {
            background: rgb(17 24 39);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-gray-900/20 backdrop-blur-lg border-b border-gray-800 fixed w-full top-0 z-50"
        aria-label="Main navigation">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <span
                        class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                        THE FRAMEWORK
                    </span>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <!-- Language Switcher -->
                    <div class="relative group h-full flex items-center">
                        <button
                            class="flex items-center text-gray-400 hover:text-cyan-400 transition-all font-medium gap-1 py-4">
                            <span class="uppercase">{{ \TheFramework\App\Lang::getLocale() }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="absolute top-10 right-0 pt-4 w-32 hidden group-hover:block z-50">
                            <div class="bg-gray-800 rounded-lg shadow-xl border border-gray-700 overflow-hidden">
                                <a href="?lang=en"
                                    class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">English
                                    🇺🇸</a>
                                <a href="?lang=id"
                                    class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">Indonesia
                                    🇮🇩</a>
                            </div>
                        </div>
                    </div>

                    <a href="https://github.com/Chandra2004/THE-FRAMEWORK" target="_blank" rel="noopener noreferrer"
                        class="text-gray-400 hover:text-cyan-400 transition-all font-medium flex items-center"
                        aria-label="GitHub Repository">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    @yield('main-content')

    @include('notification.notification')
    <!-- Footer -->
    <footer class="border-t border-gray-800 mt-24" role="contentinfo">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-gray-500">
                <p class="text-sm">
                    © 2024 THE FRAMEWORK •
                    <a href="https://www.instagram.com/chandratriantomo.2077/" target="_blank" rel="noopener noreferrer"
                        class="hover:text-cyan-400 transition-all">
                        {{ __('messages.crafted_by') }} Chandra Tri A
                    </a>
                </p>
                <div class="mt-2 flex justify-center space-x-4">
                    <a href="https://github.com/Chandra2004/THE-FRAMEWORK" target="_blank" rel="noopener noreferrer"
                        class="hover:text-cyan-400 transition-all" aria-label="GitHub Repository">
                        {{ __('messages.source_code') }}
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>
