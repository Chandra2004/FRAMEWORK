<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/png" href="{{ url('file/public/favicon.ico') }}">
    <title>Maintenance Mode | The Framework</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slowRotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @keyframes pulseGlow { 0%, 100% { box-shadow: 0 0 20px rgba(34, 211, 238, 0.2); } 50% { box-shadow: 0 0 40px rgba(34, 211, 238, 0.5); } }
        @keyframes progressPulse { 0% { width: 40%; } 50% { width: 70%; } 100% { width: 40%; } }
        .animate-fade-in { animation: fadeIn 0.8s ease-out; }
        .animate-slow-rotate { animation: slowRotate 20s linear infinite; }
        .animate-pulse-glow { animation: pulseGlow 4s ease-in-out infinite; }
        .animate-progress { animation: progressPulse 6s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gradient-to-b from-zinc-950 to-black min-h-screen text-zinc-100">
    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="max-w-lg w-full space-y-14 animate-fade-in text-center">
            <!-- Logo with Animation -->
            <div class="relative flex justify-center">
                <div class="absolute inset-0 flex items-center justify-center opacity-30">
                    <i data-lucide="settings" class="w-48 h-48 text-cyan-600 animate-slow-rotate"></i>
                </div>
                <div class="relative bg-zinc-900/80 border border-zinc-800 rounded-3xl p-10 shadow-2xl animate-pulse-glow z-10">
                    <img src="{{ url('file/public/favicon.ico') }}" alt="Logo The Framework" class="w-24 h-24 mx-auto">
                </div>
            </div>

            <div>
                <h1 class="text-2xl font-light tracking-wider text-zinc-400 uppercase">The Framework</h1>
                <h2 class="text-5xl font-bold mt-4">Under Maintenance</h2>
                <p class="text-zinc-400 mt-6 text-lg max-w-md mx-auto">
                    Kami sedang melakukan upgrade sistem untuk pengalaman yang lebih baik. Sebentar lagi kembali!
                </p>
            </div>

            <!-- Animated Progress -->
            <div class="space-y-4">
                <p class="text-zinc-500">Sedang memproses upgrade...</p>
                <div class="w-full bg-zinc-800 rounded-full h-3 overflow-hidden shadow-inner">
                    <div class="bg-gradient-to-r from-cyan-500 to-cyan-400 h-3 rounded-full animate-progress shadow-lg"></div>
                </div>
                <p class="text-sm text-zinc-400">Estimasi selesai: sekitar 2 jam</p>
            </div>

            <!-- Info Card -->
            <div class="bg-zinc-900/60 backdrop-blur-md border border-zinc-800 rounded-3xl p-10">
                <div class="flex items-center justify-center gap-4 mb-8">
                    <i data-lucide="info" class="w-6 h-6 text-cyan-400"></i>
                    <h3 class="text-xl font-bold">Informasi Maintenance</h3>
                </div>
                <div class="space-y-8 text-center">
                    <div>
                        <i data-lucide="clock" class="w-6 h-6 text-cyan-400 mb-3"></i>
                        <p class="text-zinc-400">Estimasi selesai</p>
                        <p class="text-xl font-medium">Sekitar 2 jam lagi</p>
                    </div>
                    <div>
                        <i data-lucide="mail" class="w-6 h-6 text-cyan-400 mb-3"></i>
                        <p class="text-zinc-400">Hubungi Support</p>
                        <a href="mailto:chandratriantomo123@gmail.com" class="text-cyan-400 hover:underline text-lg">chandratriantomo123@gmail.com</a>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-6 justify-center">
                <button onclick="window.location.reload()" 
                        class="inline-flex items-center justify-center gap-4 px-10 py-5 bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-white rounded-2xl font-bold text-lg transition-all shadow-xl">
                    <i data-lucide="refresh-cw" class="w-6 h-6"></i>
                    Coba Lagi
                </button>
                <a href="https://github.com/Chandra2004/FRAMEWORK" target="_blank"
                   class="inline-flex items-center justify-center gap-4 px-10 py-5 bg-zinc-800 hover:bg-zinc-700 text-white rounded-2xl font-medium text-lg transition-all border border-zinc-700">
                    <i data-lucide="github" class="w-6 h-6"></i>
                    Kunjungi GitHub
                </a>
            </div>

            <!-- Footer -->
            <div class="text-center text-sm text-zinc-600 pt-8">
                <p>&copy; 2025 The Framework. Created with ❤️ by 
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