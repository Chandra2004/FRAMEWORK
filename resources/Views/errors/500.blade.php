<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.6s ease-out; }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 min-h-screen text-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-2xl w-full text-center space-y-8 animate-fade-in">
            <!-- Error Icon -->
            <div class="flex justify-center">
                <div class="relative">
                    <div class="absolute inset-0 bg-red-500/20 rounded-full blur-2xl animate-pulse"></div>
                    <div class="relative bg-slate-800/50 border border-red-500/20 rounded-full p-6">
                        <i data-lucide="server-off" class="w-16 h-16 text-red-500"></i>
                    </div>
                </div>
            </div>

            <!-- Error Code & Message -->
            <div class="space-y-4">
                <h1 class="text-8xl sm:text-9xl font-bold bg-gradient-to-r from-red-500 to-pink-600 bg-clip-text text-transparent">
                    500
                </h1>
                <h2 class="text-3xl sm:text-4xl font-semibold text-slate-200">
                    Internal Server Error
                </h2>
                <p class="text-lg text-slate-400 max-w-md mx-auto">
                    Oops! Something went wrong on our servers. Our team has been notified and we're working to fix it.
                </p>
            </div>

            <!-- Troubleshooting Tips -->
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <i data-lucide="lightbulb" class="w-5 h-5 text-cyan-400"></i>
                    <h3 class="text-lg font-semibold text-slate-200">Troubleshooting Tips</h3>
                </div>
                <ul class="space-y-2 text-slate-300">
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 text-cyan-400 flex-shrink-0 mt-1"></i>
                        <span>Refresh the page in a few minutes</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 text-cyan-400 flex-shrink-0 mt-1"></i>
                        <span>Check your network connection</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 text-cyan-400 flex-shrink-0 mt-1"></i>
                        <span>Clear browser cache and cookies</span>
                    </li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="{{ url('/') }}" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg font-medium transition-colors">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    Back to Homepage
                </a>
                <button onclick="window.location.reload()" 
                        class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    Try Again
                </button>
                <a href="https://github.com/Chandra2004/FRAMEWORK" target="_blank"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                    <i data-lucide="help-circle" class="w-4 h-4"></i>
                    Contact Support
                </a>
            </div>

            <!-- Footer -->
            <div class="pt-8 border-t border-slate-800/50">
                <p class="text-sm text-slate-500">
                    &copy; {{ date('Y') }} The Framework. Created with ❤️ by 
                    <a href="https://github.com/Chandra2004" target="_blank" class="text-cyan-400 hover:underline">Chandra Tri Antomo</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>