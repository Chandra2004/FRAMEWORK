<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kesalahan Fatal - {{ $error_code ?? 500 }} | The Framework</title>
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

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .animate-pulse-slow {
            animation: pulse 3s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-950 via-red-950/20 to-slate-950 min-h-screen text-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="border-b border-slate-800/50 bg-slate-900/30 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i data-lucide="skull" class="w-6 h-6 text-red-500"></i>
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
                        class="inline-flex items-center gap-3 px-4 py-2 bg-red-500/10 border border-red-500/20 rounded-full animate-pulse-slow">
                        <i data-lucide="alert-octagon" class="w-5 h-5 text-red-500"></i>
                        <span class="text-red-400 font-medium">HTTP {{ $error_code ?? 500 }} - Kesalahan Sistem
                            Fatal</span>
                    </div>
                    <h1
                        class="text-6xl sm:text-7xl font-bold bg-gradient-to-r from-red-500 via-pink-500 to-red-600 bg-clip-text text-transparent">
                        Kesalahan Fatal
                    </h1>
                    <div
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/10 border border-red-500/20 rounded-full">
                        <i data-lucide="code" class="w-4 h-4 text-red-400"></i>
                        <span class="text-red-300 font-medium">{{ $type_name ?? 'E_ERROR' }}</span>
                    </div>
                    <p class="text-xl text-slate-300 max-w-2xl mx-auto">
                        {{ $message }}
                    </p>
                </div>

                <!-- Error Details Grid -->
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="file-code" class="w-4 h-4 text-red-400"></i>
                            <span class="text-sm font-medium text-slate-400">Lokasi File</span>
                        </div>
                        <p class="text-sm font-mono text-slate-200 break-all">{{ $file }}</p>
                        <p class="text-xs text-slate-500 mt-1">Baris: {{ $line }}</p>
                    </div>
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="clock" class="w-4 h-4 text-red-400"></i>
                            <span class="text-sm font-medium text-slate-400">Waktu Kejadian</span>
                        </div>
                        <p class="text-sm text-slate-200">{{ date('d M Y, H:i:s') }}</p>
                    </div>
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-red-400"></i>
                            <span class="text-sm font-medium text-slate-400">Tipe Error</span>
                        </div>
                        <p class="text-sm text-slate-200 font-mono">{{ $type_name ?? 'E_ERROR' }}</p>
                        <p class="text-xs text-slate-500 mt-1">Kode: {{ $type ?? 0 }}</p>
                    </div>
                </div>

                <!-- Code Snippet -->
                @if (!empty($code_snippet))
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                        <div
                            class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="code" class="w-4 h-4 text-red-400"></i>
                                <span class="text-sm font-medium text-slate-300">{{ basename($file) }}</span>
                            </div>
                            <span class="text-xs text-slate-500">Baris
                                {{ min(array_keys($code_snippet)) }}-{{ max(array_keys($code_snippet)) }}</span>
                        </div>
                        <div class="p-4 overflow-x-auto max-h-96 overflow-y-auto">
                            <pre class="text-sm font-mono"><code>@foreach ($code_snippet as $lineNum => $codeLine)
                                <span class="{{ $lineNum == $line ? 'bg-red-500/20 text-red-300 border-l-2 border-red-500 pl-2 -ml-2' : 'text-slate-300' }}">{{ str_pad($lineNum, 4, ' ', STR_PAD_LEFT) }} | {!! htmlspecialchars(rtrim($codeLine)) !!}</span>
                            @endforeach</code></pre>
                        </div>
                    </div>
                @endif

                <!-- Request & Environment Info -->
                <div class="grid md:grid-cols-2 gap-4">
                    @if (!empty($request_info))
                        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                            <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center gap-2">
                                <i data-lucide="globe" class="w-4 h-4 text-red-400"></i>
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
                    @endif

                    @if (!empty($environment))
                        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                            <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center gap-2">
                                <i data-lucide="server" class="w-4 h-4 text-red-400"></i>
                                <span class="text-sm font-medium text-slate-300">Lingkungan (Environment)</span>
                            </div>
                            <div class="p-4 space-y-3 text-sm">
                                <div>
                                    <span class="text-slate-500">Versi PHP:</span>
                                    <span class="text-slate-200 ml-2 font-mono">{{ $environment['php_version'] }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">Mode App:</span>
                                    <span class="text-slate-200 ml-2 font-mono">{{ $environment['app_env'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Critical Actions -->
                <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-5">
                    <div class="flex items-start gap-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <h3 class="text-red-300 font-semibold mb-2">Tindakan Segera Diperlukan</h3>
                            <ul class="space-y-2 text-sm text-slate-300">
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5"></i>
                                    <span>Periksa log error server untuk informasi lebih detail</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5"></i>
                                    <span>Review perubahan kode terakhir dan deployment</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5"></i>
                                    <span>Restart layanan aplikasi dan bersihkan cache jika perlu</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

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
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors">
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