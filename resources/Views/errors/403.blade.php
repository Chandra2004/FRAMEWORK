<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | The Framework</title>
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

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }
    </style>
</head>

<body
    class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 min-h-screen text-gray-100 flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div
            class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 backdrop-blur-xl animate-fade-in shadow-2xl relative overflow-hidden">
            <!-- Decorative Strip -->
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-red-500 via-orange-500 to-red-500"></div>

            <div class="text-center space-y-6">
                <!-- Icon -->
                <div
                    class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-red-500/10 border-2 border-red-500/20 text-red-500 animate-shake mb-4">
                    <i data-lucide="shield-alert" class="w-12 h-12"></i>
                </div>

                <!-- Error Message -->
                <div class="space-y-2">
                    <h1 class="text-4xl font-bold text-slate-100">Akses Ditolak</h1>
                    <p class="text-red-400 font-mono text-lg">Error 403 Forbidden</p>
                </div>

                <p class="text-slate-300 text-lg">
                    Maaf, Anda tidak memiliki izin untuk mengakses halaman atau sumber daya ini.
                </p>

                <!-- Possible Reasons -->
                <div class="bg-slate-900/50 rounded-xl p-6 text-left border border-slate-700/50">
                    <h3 class="flex items-center gap-2 font-semibold text-slate-200 mb-4">
                        <i data-lucide="help-circle" class="w-5 h-5 text-cyan-400"></i>
                        Mengapa hal ini terjadi?
                    </h3>
                    <ul class="space-y-3 text-slate-400">
                        <li class="flex items-start gap-3">
                            <i data-lucide="lock" class="w-4 h-4 mt-1 flex-shrink-0"></i>
                            <span>Anda belum login atau sesi login Anda telah berakhir.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i data-lucide="user-x" class="w-4 h-4 mt-1 flex-shrink-0"></i>
                            <span>Akun Anda tidak memiliki role/level yang cukup.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i data-lucide="server" class="w-4 h-4 mt-1 flex-shrink-0"></i>
                            <span>Akses dibatasi oleh firewall atau kebijakan keamanan server.</span>
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                    <a href="{{ url('/') }}"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-all shadow-lg hover:shadow-red-500/25">
                        <i data-lucide="home" class="w-5 h-5"></i>
                        Kembali ke Beranda
                    </a>
                    <a href="javascript:history.back()"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        Kembali
                    </a>
                </div>
            </div>

            <!-- Footer Small -->
            <div class="mt-8 pt-6 border-t border-slate-700/50 text-center">
                <p class="text-xs text-slate-500 font-mono">
                    ID Request: {{ uniqid() }} | IP: {{ $_SERVER['REMOTE_ADDR'] }}
                </p>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>