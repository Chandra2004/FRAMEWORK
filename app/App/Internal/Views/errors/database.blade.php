<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ url('/assets/ico/favicon-debug.ico') }}">
    <title>Gagal Koneksi Database | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

        :root {
            --bg: #0d1117;
            --card: #161b22;
            --border: rgba(248, 81, 73, 0.15);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: #c9d1d9;
        }

        .premium-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .glow-db {
            text-shadow: 0 0 30px rgba(248, 81, 73, 0.3);
        }
    </style>
</head>

<body class="p-4 md:p-12 bg-[radial-gradient(circle_at_50%_0%,_#1a0505_0%,_#0d1117_100%)]">
    <div class="max-w-5xl mx-auto space-y-8">
        <!-- Hero -->
        <div class="text-center space-y-4">
            <div
                class="inline-flex items-center gap-2 px-3 py-1 bg-red-500/10 border border-red-500/20 text-red-500 text-xs font-black rounded-full uppercase tracking-widest">
                <i data-lucide="database-zap" class="w-4 h-4"></i>
                Database Connection Error
            </div>
            <h1 class="text-4xl md:text-6xl font-black text-white glow-db">DatabaseException</h1>
            <p class="text-lg text-slate-400 max-w-2xl mx-auto">
                {{ $message ?? 'Halaman ini memerlukan koneksi database, namun sistem gagal menjangkau server database Anda.' }}
            </p>
        </div>

        <!-- Issue Grid -->
        <div class="grid md:grid-cols-2 gap-4">
            <!-- Env Config -->
            <div class="premium-card overflow-hidden">
                <div class="px-5 py-3 border-b border-white/5 bg-black/20 flex items-center justify-between">
                    <span class="text-xs font-black uppercase text-slate-500">Current Configuration</span>
                    <span class="text-[10px] text-red-500 font-bold">.env check</span>
                </div>
                <div class="p-5 space-y-3 font-mono text-xs">
                    @php
                        $envVars = [
                            'DB_HOST' => $env_values['DB_HOST'] ?? 'not set',
                            'DB_PORT' => $env_values['DB_PORT'] ?? 'not set',
                            'DB_NAME' => $env_values['DB_NAME'] ?? 'not set',
                            'DB_USER' => $env_values['DB_USER'] ?? 'not set',
                        ];
                    @endphp
                    @foreach ($envVars as $key => $value)
                        <div class="flex items-center justify-between p-2 bg-black/20 rounded border border-white/5">
                            <span class="text-slate-500">{{ $key }}</span>
                            <span
                                class="{{ $value === 'not set' ? 'text-red-500 italic' : 'text-blue-400' }}">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Troubleshooting -->
            <div class="premium-card overflow-hidden bg-gradient-to-br from-[#161b22] to-[#1c2128]">
                <div class="px-5 py-3 border-b border-white/5 bg-black/20 text-xs font-black uppercase text-slate-500">
                    Quick Fix Steps</div>
                <div class="p-5 space-y-3">
                    <div class="flex items-start gap-3">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500 mt-0.5"></i>
                        <p class="text-xs text-slate-300">Pastikan file <b>.env</b> sudah ada dan variabel DB_ sudah
                            benar.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500 mt-0.5"></i>
                        <p class="text-xs text-slate-300">Cek apakah server <b>MySQL/MariaDB</b> sudah aktif dan dapat
                            diakses.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500 mt-0.5"></i>
                        <p class="text-xs text-slate-300">Pastikan nama database di <b>DB_NAME</b> sudah dibuat di
                            server.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Errors -->
        @if (!empty($config_errors) || !empty($env_errors))
            <div class="premium-card p-6 border-red-500/30 bg-red-500/5">
                <h3 class="text-sm font-black text-red-400 uppercase tracking-tighter mb-4 flex items-center gap-2">
                    <i data-lucide="alert-octagon" class="w-4 h-4"></i>
                    Detected Anomalies
                </h3>
                <div class="space-y-3">
                    @foreach (array_merge($config_errors ?? [], $env_errors ?? []) as $error)
                        <div
                            class="flex items-start gap-3 p-3 bg-red-500/10 rounded-lg border border-red-500/20 text-xs text-red-200">
                            <i data-lucide="x-circle" class="w-4 h-4 flex-shrink-0"></i>
                            {{ $error }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Footer Actions -->
        <div class="flex items-center justify-center gap-4">
            <a href="{{ url('/') }}"
                class="px-8 py-3 bg-white text-black font-black rounded-lg hover:bg-slate-200 transition-all uppercase tracking-tighter">
                Exit to Home
            </a>
            <button onclick="location.reload()"
                class="px-8 py-3 bg-slate-900 border border-slate-700 text-white font-black rounded-lg hover:bg-slate-800 transition-all uppercase tracking-tighter">
                Retry Connection
            </button>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
