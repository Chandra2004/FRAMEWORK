<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gagal Pembayaran | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0d1117;
            color: #c9d1d9;
        }

        .glow-gold {
            text-shadow: 0 0 30px rgba(210, 153, 34, 0.3);
        }
    </style>
</head>

<body
    class="min-h-screen flex items-center justify-center p-6 bg-[radial-gradient(circle_at_50%_0%,_#1a1505_0%,_#0d1117_100%)]">
    <div class="max-w-2xl w-full text-center space-y-8">
        <div class="space-y-4">
            <div class="relative inline-block">
                <div class="absolute inset-0 bg-amber-500/10 blur-3xl rounded-full"></div>
                <i data-lucide="credit-card" class="w-24 h-24 text-amber-500 relative mx-auto mb-6"></i>
            </div>

            <h1 class="text-4xl md:text-5xl font-black text-white glow-gold tracking-tighter">Pembayaran Gagal</h1>
            <p class="text-slate-400 text-lg leading-relaxed max-w-lg mx-auto">
                Transaksi Anda tidak dapat diproses saat ini. Hal ini mungkin dikarenakan saldo tidak mencukupi, limit
                kartu, atau gangguan pada penyedia layanan pembayaran.
            </p>
        </div>

        <div class="bg-amber-500/5 border border-amber-500/20 rounded-2xl p-6 backdrop-blur-sm max-w-md mx-auto">
            <div class="flex items-start gap-4 text-left">
                <div class="w-10 h-10 bg-amber-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="info" class="w-5 h-5 text-amber-400"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-100 mb-1">Pesan Sistem</h3>
                    <p class="text-xs text-amber-200/60 leading-relaxed italic">
                        "{{ $message ?? 'Otorisasi pembayaran ditolak oleh bank atau penyedia layanan.' }}"
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
            <button onclick="window.history.back()"
                class="w-full sm:w-auto px-8 py-3 bg-amber-500 text-black font-black rounded-full hover:bg-amber-400 transition-all flex items-center justify-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Coba Lagi
            </button>
            <a href="{{ url('/') }}"
                class="w-full sm:w-auto px-8 py-3 bg-slate-900 border border-slate-700 text-white font-black rounded-full hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
                <i data-lucide="home" class="w-4 h-4"></i>
                Beranda
            </a>
        </div>

        <div class="text-[10px] text-slate-700 font-mono tracking-widest uppercase italic">
            MIDTRANS_TX_ID: {{ strtoupper(substr(md5(time()), 0, 12)) }}
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
