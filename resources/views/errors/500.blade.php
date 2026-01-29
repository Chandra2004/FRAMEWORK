<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Internal Server | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0d1117;
            color: #c9d1d9;
        }

        .glow-500 {
            text-shadow: 0 0 30px rgba(244, 63, 94, 0.4);
        }
    </style>
</head>

<body
    class="min-h-screen flex items-center justify-center p-6 bg-[radial-gradient(circle_at_50%_0%,_#1a0505_0%,_#0d1117_100%)]">
    <div class="max-w-2xl w-full text-center space-y-8">
        <div class="space-y-4">
            <div class="relative inline-block">
                <div class="absolute inset-0 bg-red-500/10 blur-3xl rounded-full"></div>
                <i data-lucide="server-off" class="w-24 h-24 text-red-500 relative mx-auto mb-6"></i>
            </div>

            <h1 class="text-7xl font-black text-white glow-500 tracking-tighter">500</h1>
            <h2 class="text-3xl font-bold text-white tracking-tight">Internal Server Error</h2>
            <p class="text-slate-400 text-lg leading-relaxed max-w-lg mx-auto">
                Terjadi kesalahan yang tidak terduga pada server kami. Jangan khawatir, tim pengembang telah diberitahu
                secara otomatis.
            </p>
        </div>

        <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-6 backdrop-blur-sm max-w-md mx-auto">
            <div class="flex items-start gap-4 text-left">
                <div class="w-10 h-10 bg-red-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="info" class="w-5 h-5 text-red-400"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-red-100 mb-1">Dukungan Sistem</h3>
                    <p class="text-xs text-red-200/60 leading-relaxed">
                        Jika Anda adalah pengembang, silakan aktifkan <code
                            class="bg-red-500/20 px-1 rounded">APP_DEBUG=true</code> di file .env Anda untuk melihat
                        detail kesalahan teknis.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
            <button onclick="location.reload()"
                class="w-full sm:w-auto px-8 py-3 bg-white text-black font-black rounded-full hover:bg-slate-200 transition-all flex items-center justify-center gap-2">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                Segarkan Halaman
            </button>
            <a href="{{ url('/') }}"
                class="w-full sm:w-auto px-8 py-3 bg-slate-900 border border-slate-700 text-white font-black rounded-full hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                <i data-lucide="home" class="w-4 h-4"></i>
                Beranda
            </a>
        </div>

        <div class="text-[10px] text-slate-700 font-mono tracking-widest uppercase italic">
            Reference ID: {{ substr(md5(time()), 0, 10) }}
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
