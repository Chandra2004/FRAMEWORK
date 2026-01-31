<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ url('/assets/ico/favicon-debug.ico') }}">
    <title>Sistem Dalam Pemeliharaan | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0d1117;
            color: #c9d1d9;
        }

        .glow-build {
            text-shadow: 0 0 30px rgba(56, 189, 248, 0.3);
        }
    </style>
</head>

<body
    class="min-h-screen flex items-center justify-center p-6 bg-[radial-gradient(circle_at_50%_0%,_#161b22_0%,_#0d1117_100%)]">
    <div class="max-w-2xl w-full text-center space-y-12">
        <div class="relative inline-block">
            <div class="absolute inset-0 bg-blue-500/10 blur-3xl rounded-full"></div>
            <i data-lucide="wrench" class="w-24 h-24 text-blue-500 relative mx-auto mb-6 animate-bounce"></i>
        </div>

        <div class="space-y-4">
            <div
                class="inline-flex items-center gap-2 px-3 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-black rounded-full uppercase tracking-widest">
                Scheduled Maintenance
            </div>
            <h1 class="text-5xl md:text-6xl font-black text-white glow-build tracking-tight italic">UNDER CONSTRUCTION
            </h1>
            <p class="text-xl text-slate-400 leading-relaxed max-w-lg mx-auto">
                Kami sedang melakukan pemeliharaan rutin untuk meningkatkan performa layanan. Kami akan segera kembali
                dalam beberapa saat.
            </p>
        </div>

        <div
            class="premium-card bg-slate-900/40 border border-white/5 rounded-2xl p-8 backdrop-blur-sm max-w-sm mx-auto">
            <div class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Estimasi Selesai</div>
            <div class="text-3xl font-mono text-blue-400 font-bold tracking-tighter">SOON</div>
            <div class="w-full bg-slate-800 h-1.5 rounded-full mt-6 overflow-hidden">
                <div class="bg-blue-500 h-full w-2/3 animate-pulse"></div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="mailto:support@framework.com"
                class="text-slate-500 hover:text-white transition-all text-sm font-medium flex items-center gap-2">
                <i data-lucide="mail" class="w-4 h-4"></i>
                Butuh bantuan mendesak?
            </a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
