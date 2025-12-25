<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gagal Koneksi Database - 500 | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 min-h-screen text-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="border-b border-slate-800/50 bg-slate-900/30 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i data-lucide="database-off" class="w-6 h-6 text-red-500"></i>
                        <span
                            class="text-xl font-semibold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                            The Framework
                        </span>
                    </div>
                    <a href="https://github.com/Chandra2004/FRAMEWORK" target="_blank"
                        class="text-slate-400 hover:text-cyan-400 transition-colors">
                        <i data-lucide="github" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="space-y-6 animate-fade-in">
                <!-- Error Header -->
                <div class="text-center space-y-4">
                    <div
                        class="inline-flex items-center gap-3 px-4 py-2 bg-red-500/10 border border-red-500/20 rounded-full">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500"></i>
                        <span class="text-red-400 font-medium">HTTP 500 - Koneksi Database Diperlukan</span>
                    </div>
                    <h1
                        class="text-6xl sm:text-7xl font-bold bg-gradient-to-r from-red-500 via-pink-500 to-red-600 bg-clip-text text-transparent">
                        DatabaseException
                    </h1>
                    <p class="text-xl text-slate-300 max-w-2xl mx-auto">
                        {{ $message ?? 'Halaman ini memerlukan koneksi database, namun koneksi tidak dapat dibuat.' }}
                    </p>
                </div>

                <!-- Error Details Grid -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="database" class="w-4 h-4 text-red-400"></i>
                            <span class="text-sm font-medium text-slate-400">Status Koneksi</span>
                        </div>
                        <p class="text-sm text-slate-200">Gagal terhubung ke database</p>
                        <p class="text-xs text-slate-500 mt-1">Diperlukan untuk operasi ini</p>
                    </div>
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="clock" class="w-4 h-4 text-red-400"></i>
                            <span class="text-sm font-medium text-slate-400">Waktu Kejadian</span>
                        </div>
                        <p class="text-sm text-slate-200">{{ date('d M Y, H:i:s') }}</p>
                    </div>
                </div>

                <!-- Configuration Errors -->
                @if (!empty($config_errors))
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                        <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-red-400"></i>
                            <span class="text-sm font-medium text-slate-300">Kesalahan Konfigurasi</span>
                        </div>
                        <div class="p-4 space-y-2">
                            @foreach ($config_errors as $error)
                                <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-3">
                                    <div class="flex items-start gap-2">
                                        <i data-lucide="x-circle" class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5"></i>
                                        <span class="text-sm text-slate-300">{{ $error }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Environment Variable Errors (Typos) -->
                @if (!empty($env_errors))
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                        <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center gap-2">
                            <i data-lucide="file-warning" class="w-4 h-4 text-amber-400"></i>
                            <span class="text-sm font-medium text-slate-300">Kemungkinan Typo di File .env</span>
                        </div>
                        <div class="p-4 space-y-2">
                            <p class="text-xs text-slate-400 mb-3">
                                Variabel lingkungan berikut mungkin salah ketik. Mohon periksa file <code
                                    class="bg-slate-900 px-2 py-1 rounded text-amber-300 font-mono text-xs">.env</code>
                                Anda:
                            </p>
                            @foreach ($env_errors as $error)
                                <div class="bg-amber-500/10 border border-amber-500/20 rounded-lg p-3">
                                    <div class="flex items-start gap-2">
                                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5"></i>
                                        <span class="text-sm text-slate-300">{{ $error }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Required Environment Variables & Troubleshooting -->
                <div class="grid md:grid-cols-2 gap-4">
                    <!-- Required Environment Variables -->
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                        <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center gap-2">
                            <i data-lucide="info" class="w-4 h-4 text-cyan-400"></i>
                            <span class="text-sm font-medium text-slate-300">Variabel Lingkungan DB</span>
                        </div>
                        <div class="p-4">
                            <div class="bg-slate-900/50 rounded-lg p-4 font-mono text-xs space-y-2">
                                @php
                                    $envVars = [
                                        'DB_HOST' => $env_values['DB_HOST'] ?? 'not set',
                                        'DB_PORT' => $env_values['DB_PORT'] ?? 'not set',
                                        'DB_NAME' => $env_values['DB_NAME'] ?? 'not set',
                                        'DB_USER' => $env_values['DB_USER'] ?? 'not set',
                                        'DB_PASS' => $env_values['DB_PASS'] ?? 'not set',
                                    ];
                                @endphp
                                @foreach ($envVars as $key => $value)
                                    <div class="flex items-center gap-2">
                                        @if ($value !== 'not set')
                                            <i data-lucide="check-circle" class="w-3 h-3 text-green-400"></i>
                                            <span class="text-slate-300">{{ $key }}={{ $value }}</span>
                                        @else
                                            <i data-lucide="x-circle" class="w-3 h-3 text-red-400"></i>
                                            <span class="text-red-300">{{ $key }}=<span class="text-slate-500 italic">not
                                                    set</span></span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Troubleshooting Steps -->
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                        <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center gap-2">
                            <i data-lucide="lightbulb" class="w-4 h-4 text-cyan-400"></i>
                            <span class="text-sm font-medium text-slate-300">Langkah Perbaikan</span>
                        </div>
                        <div class="p-4 space-y-2">
                            <div class="flex items-start gap-2 text-xs text-slate-300">
                                <i data-lucide="check" class="w-3 h-3 text-cyan-400 flex-shrink-0 mt-1"></i>
                                <span>Pastikan file <code class="bg-slate-900 px-1 rounded text-cyan-300">.env</code>
                                    tersedia</span>
                            </div>
                            <div class="flex items-start gap-2 text-xs text-slate-300">
                                <i data-lucide="check" class="w-3 h-3 text-cyan-400 flex-shrink-0 mt-1"></i>
                                <span>Verifikasi semua variabel DB_* sudah diisi</span>
                            </div>
                            <div class="flex items-start gap-2 text-xs text-slate-300">
                                <i data-lucide="check" class="w-3 h-3 text-cyan-400 flex-shrink-0 mt-1"></i>
                                <span>Cek typo (misal: DB_HOST bukan DBHOST)</span>
                            </div>
                            <div class="flex items-start gap-2 text-xs text-slate-300">
                                <i data-lucide="check" class="w-3 h-3 text-cyan-400 flex-shrink-0 mt-1"></i>
                                <span>Pastikan server database (MySQL) berjalan</span>
                            </div>
                            <div class="flex items-start gap-2 text-xs text-slate-300">
                                <i data-lucide="check" class="w-3 h-3 text-cyan-400 flex-shrink-0 mt-1"></i>
                                <span>Verifikasi username dan password benar</span>
                            </div>
                            <div class="flex items-start gap-2 text-xs text-slate-300">
                                <i data-lucide="check" class="w-3 h-3 text-cyan-400 flex-shrink-0 mt-1"></i>
                                <span>Cek apakah nama database sudah dibuat</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Request & Environment Info -->
                @if (!empty($request_info))
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                            <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center gap-2">
                                <i data-lucide="globe" class="w-4 h-4 text-cyan-400"></i>
                                <span class="text-sm font-medium text-slate-300">Info Request</span>
                            </div>
                            <div class="p-4 space-y-3 text-sm">
                                <div>
                                    <span class="text-slate-500">Metode:</span>
                                    <span class="text-slate-200 ml-2 font-mono">{{ $request_info['method'] }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">URI:</span>
                                    <span
                                        class="text-slate-200 ml-2 font-mono text-xs break-all">{{ $request_info['uri'] }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">IP:</span>
                                    <span class="text-slate-200 ml-2 font-mono">{{ $request_info['ip'] }}</span>
                                </div>
                            </div>
                        </div>

                        @if (!empty($environment))
                            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                                <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center gap-2">
                                    <i data-lucide="server" class="w-4 h-4 text-cyan-400"></i>
                                    <span class="text-sm font-medium text-slate-300">Lingkungan</span>
                                </div>
                                <div class="p-4 space-y-3 text-sm">
                                    <div>
                                        <span class="text-slate-500">Versi PHP:</span>
                                        <span
                                            class="text-slate-200 ml-2 font-mono">{{ $environment['php_version'] ?? PHP_VERSION }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-500">Mode App:</span>
                                        <span
                                            class="text-slate-200 ml-2 font-mono">{{ $environment['app_env'] ?? 'unknown' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex flex-wrap gap-3 justify-center pt-4">
                    <a href="{{ url('/') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg font-medium transition-colors">
                        <i data-lucide="home" class="w-4 h-4"></i>
                        Beranda
                    </a>
                    <button onclick="window.location.reload()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        Muat Ulang
                    </button>
                    <a href="https://github.com/Chandra2004/FRAMEWORK/issues/new" target="_blank"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                        <i data-lucide="bug" class="w-4 h-4"></i>
                        Lapor Bug
                    </a>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-800/50 mt-12 py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-slate-500">
                <p>&copy; {{ date('Y') }} The Framework. Dibuat dengan ❤️ oleh <a href="https://github.com/Chandra2004"
                        target="_blank" class="text-cyan-400 hover:underline">Chandra Tri Antomo</a></p>
            </div>
        </footer>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>