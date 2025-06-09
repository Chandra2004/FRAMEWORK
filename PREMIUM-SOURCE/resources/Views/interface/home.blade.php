<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags -->
    <title>THE FRAMEWORK - Modern PHP Framework with Database Migrations & REST API</title>
    <meta name="description" content="THE FRAMEWORK: High-performance PHP framework with Laravel-like features including database migrations, seeders, RESTful API support, and elegant syntax. Perfect for modern web development.">
    <meta name="keywords" content="PHP Framework, MVC, Database Migrations, REST API, Web Development, Laravel Alternative, MySQL, Composer">
    <meta name="author" content="Chandra Tri Antomo">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://kiwkiw-mvc.tech/">
    <meta property="og:title" content="THE FRAMEWORK - Modern PHP Framework for Web Artisans">
    <meta property="og:description" content="Build scalable web applications with THE FRAMEWORK framework featuring database migrations, REST API support, and enterprise-grade security">
    <meta property="og:image" content="https://kiwkiw-mvc.tech/images/og-banner.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://kiwkiw-mvc.tech/">
    <meta property="twitter:title" content="THE FRAMEWORK - Modern PHP Framework for Web Artisans">
    <meta property="twitter:description" content="High-performance PHP framework with Laravel-like features and minimalist architecture">
    <meta property="twitter:image" content="https://kiwkiw-mvc.tech/images/og-banner.jpg">

    <!-- Canonical -->
    <link rel="canonical" href="https://kiwkiw-mvc.tech/">

    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐦</text></svg>">

    <!-- Preload Resources -->
    <link rel="preload" href="https://cdn.tailwindcss.com" as="script">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://unpkg.com">

    <!-- Stylesheets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "THE FRAMEWORK",
        "operatingSystem": "PHP 8+",
        "applicationCategory": "Web Framework",
        "softwareVersion": "1.0.0",
        "description": "Modern PHP framework with database migrations and REST API support",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "author": {
            "@type": "Person",
            "name": "Chandra Tri Antomo",
            "url": "https://github.com/Chandra2004"
        }
    }
    </script>

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
    <nav class="bg-gray-900/20 backdrop-blur-lg border-b border-gray-800 fixed w-full top-0 z-50" aria-label="Main navigation">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <span class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                        THE FRAMEWORK
                    </span>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="https://github.com/Chandra2004/THE-FRAMEWORK" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="text-gray-400 hover:text-cyan-400 transition-all font-medium flex items-center"
                       aria-label="GitHub Repository">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <header class="pt-32 pb-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" role="banner">
            <div class="text-center">
                <h1 class="text-5xl font-bold text-gray-100 mb-6 leading-tight">
                    Build Scalable Web Apps
                    <span class="block mt-4 bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                        With Modern PHP Framework
                    </span>
                </h1>
                <p class="text-xl text-gray-400 mb-8 max-w-3xl mx-auto">
                    THE FRAMEWORK offers developers an elegant toolkit featuring database migrations, REST API support,
                    and enterprise-grade security in a lightweight package.
                </p>
                
                <!-- CTA Button -->
                <div class="flex justify-center space-x-4 mb-16">
                    <a href="{{ url('user') }}"
                       rel="noopener noreferrer"
                       class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white px-8 py-4 rounded-lg font-medium 
                              hover:from-cyan-600 hover:to-blue-700 transition-all shadow-2xl hover:shadow-cyan-500/20
                              flex items-center gap-2"
                       aria-label="Get Started with THE FRAMEWORK">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Start Building
                    </a>
                </div>
            </div>
        </header>

        <!-- Features Section -->
        <section class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" aria-labelledby="features-heading">
            <h2 id="features-heading" class="sr-only">Key Features</h2>
            
            <div class="grid md:grid-cols-3 gap-8 mb-24">
                <!-- Feature 1 -->
                <article class="bg-gray-800/50 p-8 rounded-2xl hover:bg-gray-800/80 transition-all border border-gray-700/50 hover:border-cyan-400/30 text-center">
                    <div class="w-14 h-14 bg-cyan-400/10 rounded-xl mb-6 mx-auto flex items-center justify-center">
                        <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-100 mb-3">Lightning Performance</h3>
                    <p class="text-gray-400 px-4">Optimized stack with Opcache integration and smart routing system</p>
                </article>

                <!-- Feature 2 -->
                <article class="bg-gray-800/50 p-8 rounded-2xl hover:bg-gray-800/80 transition-all border border-gray-700/50 hover:border-cyan-400/30 text-center">
                    <div class="w-14 h-14 bg-cyan-400/10 rounded-xl mb-6 mx-auto flex items-center justify-center">
                        <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-100 mb-3">Secure Architecture</h3>
                    <p class="text-gray-400 px-4">Built-in protection against SQLi, XSS, and CSRF vulnerabilities</p>
                </article>

                <!-- Feature 3 -->
                <article class="bg-gray-800/50 p-8 rounded-2xl hover:bg-gray-800/80 transition-all border border-gray-700/50 hover:border-cyan-400/30 text-center">
                    <div class="w-14 h-14 bg-cyan-400/10 rounded-xl mb-6 mx-auto flex items-center justify-center">
                        <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16m-7 6h7"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-100 mb-3">REST API Ready</h3>
                    <p class="text-gray-400 px-4">Built-in JWT authentication and API versioning support</p>
                </article>
            </div>
        </section>

        <!-- Database Status -->
        <section class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto text-center" aria-labelledby="db-status">
            <h2 id="db-status" class="sr-only">Database Connection Status</h2>
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700/50 max-w-md mx-auto">
                <div class="flex items-center justify-center space-x-3">
                    @if ($status == "success")
                        <div class="w-9 h-9 bg-cyan-400/10 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="text-center">
                            <p class="text-cyan-400 font-medium">Database Connected</p>
                            <p class="text-gray-400 text-sm">MySQL Server 8.0+ Ready</p>
                        </div>
                    @else
                        <div class="w-9 h-9 bg-red-400/10 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div class="text-center">
                            <p class="text-red-400 font-medium">Connection Error</p>
                            <p class="text-gray-400 text-sm">Check MySQL Configuration</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-800 mt-24" role="contentinfo">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-gray-500">
                <p class="text-sm">
                    © 2024 THE FRAMEWORK • 
                    <a href="https://www.instagram.com/chandratriantomo.2077/" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="hover:text-cyan-400 transition-all">
                        Crafted by Chandra Tri A
                    </a>
                </p>
                <div class="mt-2 flex justify-center space-x-4">
                    <a href="https://github.com/Chandra2004/THE-FRAMEWORK" 
                       target="_blank"
                       rel="noopener noreferrer"
                       class="hover:text-cyan-400 transition-all"
                       aria-label="GitHub Repository">
                        Source Code
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
