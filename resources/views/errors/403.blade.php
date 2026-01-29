<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Dilarang | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0d1117;
            color: #c9d1d9;
        }

        .glow-security {
            text-shadow: 0 0 30px rgba(56, 189, 248, 0.4);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-xl w-full text-center space-y-8">
        <div class="relative inline-block">
            <div class="absolute inset-0 bg-blue-500/20 blur-3xl rounded-full"></div>
            <i data-lucide="shield-off" class="w-32 h-32 text-blue-500 relative mx-auto animate-pulse"></i>
        </div>

        <div class="space-y-4">
            <h1 class="text-8xl font-black text-white glow-security tracking-tighter">403</h1>
            <h2 class="text-2xl font-bold text-slate-200">Akses Dilarang (Forbidden)</h2>
            <p class="text-slate-400 leading-relaxed">
                Maaf, Anda tidak memiliki izin untuk mengakses halaman atau sumber daya ini. Ini mungkin karena sesi
                Anda berakhir atau level akun Anda tidak mencukupi.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 text-left">
            <div class="bg-slate-900/50 border border-white/5 rounded-xl p-4 flex items-center gap-4">
                <div class="w-10 h-10 bg-blue-500/10 rounded-lg flex items-center justify-center">
                    <i data-lucide="lock" class="w-5 h-5 text-blue-400"></i>
                </div>
                <div>
                    <div class="text-[10px] font-black text-slate-500 uppercase">Reason Code</div>
                    <div class="text-sm font-mono text-slate-300">AUTH_PERMISSION_DENIED</div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center gap-4 pt-4">
            <a href="{{ url('/') }}"
                class="px-8 py-3 bg-white text-black font-black rounded-full hover:bg-slate-200 transition-all flex items-center gap-2">
                <i data-lucide="home" class="w-4 h-4"></i>
                Kembali ke Beranda
            </a>
            <button onclick="history.back()"
                class="px-8 py-3 bg-slate-900 border border-slate-700 text-white font-black rounded-full hover:bg-slate-800 transition-all">
                Kembali
            </button>
        </div>

        <div class="text-xs text-slate-600 pt-8 font-mono">
            IP: {{ $_SERVER['REMOTE_ADDR'] }} | {{ date('Y-m-d H:i:s') }}
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
