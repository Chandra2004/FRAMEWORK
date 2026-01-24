<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Server | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse-red {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
            }

            50% {
                box-shadow: 0 0 40px rgba(239, 68, 68, 0.4);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .pulse-effect {
            animation: pulse-red 3s infinite;
        }
    </style>
</head>

<body
    class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 min-h-screen text-gray-100 flex items-center justify-center p-4">
    <div class="max-w-xl w-full text-center space-y-8 animate-fade-in">
        <!-- Icon -->
        <div
            class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-slate-800 border border-slate-700 pulse-effect mb-6">
            <i data-lucide="server-crash" class="w-12 h-12 text-red-500"></i>
        </div>

        <!-- Message -->
        <div class="space-y-4">
            <h1 class="text-6xl font-bold bg-gradient-to-r from-red-500 to-orange-500 bg-clip-text text-transparent">
                500
            </h1>
            <h2 class="text-2xl font-semibold text-slate-200">Terjadi Kesalahan Server</h2>
            <p class="text-slate-400">
                Ups! Sesuatu yang buruk terjadi di server kami. Tim teknis kami telah diberitahu dan sedang
                memperbaikinya.
            </p>
        </div>

        <!-- Technical Note (Hidden details for security) -->
        <div class="bg-slate-800/30 border border-slate-700/50 rounded-lg p-4 text-xs text-slate-500 font-mono">
            <p>Error ID: {{ strtoupper(uniqid('ERR-')) }}</p>
            <p class="mt-1">Silakan coba muat ulang halaman atau hubungi dukungan jika masalah berlanjut.</p>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap gap-3 justify-center pt-4">
            <button onclick="window.location.reload()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg font-medium transition-colors">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                Coba Lagi
            </button>
            <a href="{{ url('/') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-lg font-medium transition-colors border border-slate-700">
                <i data-lucide="home" class="w-4 h-4"></i>
                Beranda
            </a>
        </div>

        <!-- Footer -->
        <div class="pt-8 text-sm text-slate-600">
            &copy; {{ date('Y') }} The Framework
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>