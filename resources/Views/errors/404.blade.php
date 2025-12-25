<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | The Framework</title>
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

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 min-h-screen text-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-2xl w-full text-center space-y-8 animate-fade-in">
            <!-- Error Icon -->
            <div class="flex justify-center animate-float">
                <div class="relative">
                    <div class="absolute inset-0 bg-cyan-500/20 rounded-full blur-2xl"></div>
                    <div class="relative bg-slate-800/50 border border-cyan-500/20 rounded-full p-6">
                        <i data-lucide="file-question" class="w-16 h-16 text-cyan-500"></i>
                    </div>
                </div>
            </div>

            <!-- Error Code & Message -->
            <div class="space-y-4">
                <h1
                    class="text-8xl sm:text-9xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                    404
                </h1>
                <h2 class="text-3xl sm:text-4xl font-semibold text-slate-200">
                    Halaman Tidak Ditemukan
                </h2>
                <p class="text-lg text-slate-400 max-w-md mx-auto">
                    Ups! Halaman yang Anda cari tampaknya sudah menghilang dari radar kami. Mungkin sudah pindah atau
                    memang tidak pernah ada.
                </p>
            </div>

            <!-- Suggestions -->
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <i data-lucide="search" class="w-5 h-5 text-cyan-400"></i>
                    <h3 class="text-lg font-semibold text-slate-200">Apa yang bisa Anda lakukan?</h3>
                </div>
                <ul class="space-y-2 text-slate-300">
                    <li class="flex items-start gap-2">
                        <i data-lucide="arrow-right" class="w-4 h-4 text-cyan-400 flex-shrink-0 mt-1"></i>
                        <span>Periksa kembali URL untuk kesalahan ketik</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="arrow-right" class="w-4 h-4 text-cyan-400 flex-shrink-0 mt-1"></i>
                        <span>Kembali ke beranda dan navigasi ulang dari sana</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="arrow-right" class="w-4 h-4 text-cyan-400 flex-shrink-0 mt-1"></i>
                        <span>Cek <a href="https://github.com/Chandra2004/FRAMEWORK" target="_blank"
                                class="text-cyan-400 hover:underline">dokumentasi kami</a></span>
                    </li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="{{ url('/') }}"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg font-medium transition-colors">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    Kembali ke Beranda
                </a>
                <button onclick="history.back()"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Kembali
                </button>
            </div>

            <!-- Footer -->
            <div class="pt-8 border-t border-slate-800/50">
                <p class="text-sm text-slate-500">
                    &copy; {{ date('Y') }} The Framework. Dibuat dengan ❤️ oleh
                    <a href="https://github.com/Chandra2004" target="_blank"
                        class="text-cyan-400 hover:underline">Chandra Tri Antomo</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>