<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes progress {
            0% { width: 0%; }
            50% { width: 50%; }
            100% { width: 100%; }
        }
        .animate-fade-in { animation: fadeIn 0.6s ease-out; }
        .animate-spin-slow { animation: spin-slow 3s linear infinite; }
        .animate-progress { animation: progress 2s ease-in-out infinite; }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-950 via-blue-950/20 to-slate-950 min-h-screen text-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-2xl w-full text-center space-y-8 animate-fade-in">
            <!-- Maintenance Icon -->
            <div class="flex justify-center">
                <div class="relative">
                    <div class="absolute inset-0 bg-blue-500/20 rounded-full blur-2xl"></div>
                    <div class="relative bg-slate-800/50 border border-blue-500/20 rounded-full p-6">
                        <i data-lucide="wrench" class="w-16 h-16 text-blue-500 animate-spin-slow"></i>
                    </div>
                </div>
            </div>

            <!-- Message -->
            <div class="space-y-4">
                <h1 class="text-5xl sm:text-6xl font-bold bg-gradient-to-r from-blue-400 to-cyan-500 bg-clip-text text-transparent">
                    Under Maintenance
                </h1>
                <p class="text-xl text-slate-300 max-w-md mx-auto">
                    We're working hard to improve our website. We'll be back soon!
                </p>
            </div>

            <!-- Progress Bar -->
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-full h-2.5 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-full rounded-full animate-progress"></div>
            </div>

            <!-- Information -->
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 space-y-4">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <i data-lucide="info" class="w-5 h-5 text-blue-400"></i>
                    <h3 class="text-lg font-semibold text-slate-200">Maintenance Information</h3>
                </div>
                <div class="space-y-3 text-left">
                    <div class="flex items-center gap-3">
                        <i data-lucide="clock" class="w-4 h-4 text-blue-400 flex-shrink-0"></i>
                        <div>
                            <p class="text-sm text-slate-400">Estimated completion</p>
                            <p class="text-slate-200 font-medium">2 hours</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i data-lucide="mail" class="w-4 h-4 text-blue-400 flex-shrink-0"></i>
                        <div>
                            <p class="text-sm text-slate-400">Contact Support</p>
                            <a href="mailto:chandratriantomo123@gmail.com" class="text-blue-400 hover:underline">
                                chandratriantomo123@gmail.com
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap gap-3 justify-center">
                <button onclick="window.location.reload()" 
                        class="inline-flex items-center gap-2 px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    Try Again Later
                </button>
                <a href="https://github.com/Chandra2004/FRAMEWORK" target="_blank"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                    <i data-lucide="github" class="w-4 h-4"></i>
                    Visit GitHub
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