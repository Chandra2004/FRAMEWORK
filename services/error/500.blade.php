<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags -->
    <title>Server Error - Kiwkiw MVC</title>
    <meta name="description" content="Internal server error occurred">
    <meta name="keywords" content="500 Error, Server Error, Kiwkiw MVC">
    <meta name="author" content="Chandra Tri Antomo">
    
    <!-- Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
    
    <style>
        @keyframes pulse-opacity {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .server-pulse {
            animation: pulse-opacity 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-gray-900/20 backdrop-blur-lg border-b border-gray-800 fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <span class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                        Kiwkiw MVC
                    </span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="https://github.com/Chandra2004/Kiwkiw-Native.git" 
                       target="_blank" 
                       class="text-gray-400 hover:text-cyan-400 transition-all flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <!-- GitHub icon -->
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-32 pb-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="text-center">
            <div class="max-w-2xl mx-auto">
                <!-- Animated Server Icon -->
                <div class="server-pulse mb-12 text-cyan-400/20">
                    <svg class="w-32 h-32 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>

                <!-- Error Message -->
                <h1 class="text-8xl font-bold bg-gradient-to-r from-red-400 to-pink-500 bg-clip-text text-transparent mb-8">
                    500
                </h1>
                <h2 class="text-3xl font-semibold text-gray-100 mb-4">
                    Internal Server Error
                </h2>
                <p class="text-lg text-gray-400 mb-8 max-w-xl mx-auto">
                    Oops! Something went wrong on our servers. Our team has been notified and we're working to fix it.
                </p>

                <!-- Troubleshooting Tips -->
                <div class="bg-gray-800/50 p-6 rounded-xl border border-red-400/20 mb-12 text-left">
                    <div class="flex items-start gap-4">
                        <div class="bg-red-400/10 p-3 rounded-lg mt-1">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-gray-100 font-medium mb-2">Troubleshooting Tips</h3>
                            <ul class="list-disc list-inside text-gray-400 space-y-2">
                                <li>Refresh the page in a few minutes</li>
                                <li>Check your network connection</li>
                                <li>Clear browser cache</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- CTA Buttons -->
                <div class="flex justify-center gap-4">
                    <a href="{{ url('/') }}" 
                       class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white px-6 py-3 rounded-lg font-medium 
                              hover:from-cyan-600 hover:to-blue-700 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Back to Homepage
                    </a>
                    
                    <a href="https://github.com/Chandra2004/Kiwkiw-Native" target="_blank"
                       class="px-6 py-3 rounded-lg font-medium border border-gray-700/50 
                              hover:border-cyan-400/30 text-cyan-400 hover:text-cyan-300 transition-all">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Error Tracking Integration (Example) -->
    <script>
        // Example error tracking code
        window.onerror = function(msg, url, line) {
            if (typeof ga === 'function') {
                ga('send', 'exception', {
                    exDescription: msg + ' at ' + url + ':' + line,
                    exFatal: true
                });
            }
        };
    </script>

    <!-- Footer -->
    <footer class="border-t border-gray-800 mt-24">
        <!-- ... (salin bagian footer dari sebelumnya) ... -->
    </footer>
</body>
</html>