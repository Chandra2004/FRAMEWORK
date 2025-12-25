<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Forbidden | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-fade-in { animation: fadeIn 0.6s ease-out; }
        .animate-shake { animation: shake 0.5s ease-in-out; }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-950 via-amber-950/10 to-slate-950 min-h-screen text-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-2xl w-full text-center space-y-8 animate-fade-in">
            <!-- Error Icon -->
            <div class="flex justify-center">
                <div class="relative animate-shake">
                    <div class="absolute inset-0 bg-amber-500/20 rounded-full blur-2xl"></div>
                    <div class="relative bg-slate-800/50 border border-amber-500/20 rounded-full p-6">
                        <i data-lucide="lock" class="w-16 h-16 text-amber-500"></i>
                    </div>
                </div>
            </div>

            <!-- Error Code & Message -->
            <div class="space-y-4">
                <h1 class="text-8xl sm:text-9xl font-bold bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">
                    403
                </h1>
                <h2 class="text-3xl sm:text-4xl font-semibold text-slate-200">
                    Access Forbidden
                </h2>
                <p class="text-lg text-slate-400 max-w-md mx-auto">
                    You don't have permission to access this resource. Please check your credentials or contact the administrator.
                </p>
            </div>

            <!-- Authorization Tips -->
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <i data-lucide="shield-alert" class="w-5 h-5 text-amber-400"></i>
                    <h3 class="text-lg font-semibold text-slate-200">Authorization Tips</h3>
                </div>
                <ul class="space-y-2 text-slate-300">
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 text-amber-400 flex-shrink-0 mt-1"></i>
                        <span>Ensure you're logged in with the correct account</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 text-amber-400 flex-shrink-0 mt-1"></i>
                        <span>Check your user permissions</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 text-amber-400 flex-shrink-0 mt-1"></i>
                        <span>Verify your account has the required role</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-4 h-4 text-amber-400 flex-shrink-0 mt-1"></i>
                        <span>Contact your administrator for access rights</span>
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
                <a href="{{ url('/login') }}" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-medium transition-colors">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    Login to Another Account
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