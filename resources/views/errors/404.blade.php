<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0d1117;
            color: #c9d1d9;
        }

        .glow-404 {
            text-shadow: 0 0 30px rgba(148, 163, 184, 0.3);
        }
    </style>
</head>

<body
    class="min-h-screen flex items-center justify-center p-6 bg-[radial-gradient(circle_at_50%_50%,_#161b22_0%,_#0d1117_100%)]">
    <div class="max-w-2xl w-full text-center space-y-8">
        <div class="space-y-4">
            <h1
                class="text-[12rem] font-black text-white glow-404 leading-none tracking-tighter opacity-10 select-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                404</h1>

            <div class="relative">
                <i data-lucide="map" class="w-24 h-24 text-slate-500 mx-auto mb-6"></i>
                <h2 class="text-4xl font-bold text-white tracking-tight">Halaman Hilang?</h2>
                <p class="text-slate-400 text-lg mt-4 max-w-md mx-auto">
                    Kami mencari di mana-mana, tapi sepertinya halaman yang Anda cari telah dipindahkan atau tidak
                    pernah ada.
                </p>
            </div>
        </div>

        <div class="bg-slate-900/40 border border-white/5 rounded-2xl p-6 backdrop-blur-sm max-w-sm mx-auto">
            <div class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4 leading-none">Mungkin yang Anda
                cari:</div>
            <div class="space-y-2">
                <a href="{{ url('/') }}"
                    class="flex items-center gap-3 p-3 bg-white/5 hover:bg-white/10 rounded-xl transition-all group">
                    <i data-lucide="home" class="w-4 h-4 text-blue-400"></i>
                    <span class="text-sm font-medium">Beranda Utama</span>
                    <i data-lucide="chevron-right"
                        class="w-4 h-4 ml-auto opacity-0 group-hover:opacity-100 transition-all"></i>
                </a>
                <a href="https://github.com/Chandra2004/FRAMEWORK" target="_blank"
                    class="flex items-center gap-3 p-3 bg-white/5 hover:bg-white/10 rounded-xl transition-all group">
                    <i data-lucide="github" class="w-4 h-4 text-slate-400"></i>
                    <span class="text-sm font-medium">Repositori Github</span>
                    <i data-lucide="chevron-right"
                        class="w-4 h-4 ml-auto opacity-0 group-hover:opacity-100 transition-all"></i>
                </a>
            </div>
        </div>

        <div class="flex items-center justify-center gap-4 pt-4">
            <button onclick="history.back()"
                class="px-8 py-3 bg-slate-100 text-black font-black rounded-full hover:bg-white transition-all flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Kembali
            </button>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
