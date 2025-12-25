<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/x-icon" href="{{ url('file/public/favicon.ico') }}">
    <title>Payment Required | The Framework</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(34, 211, 238, 0.2); }
            50% { box-shadow: 0 0 40px rgba(34, 211, 238, 0.4); }
        }
        .animate-fade-in { animation: fadeIn 0.8s ease-out; }
        .animate-pulse-glow { animation: pulseGlow 4s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gradient-to-b from-zinc-950 to-black min-h-screen text-zinc-100">
    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="max-w-lg w-full space-y-14 animate-fade-in">
            <!-- Header with Logo -->
            <div class="text-center space-y-8">
                <div class="flex justify-center">
                    <div class="bg-zinc-900/80 border border-zinc-800 rounded-3xl p-10 shadow-2xl animate-pulse-glow">
                        <img src="{{ url('file/public/favicon.ico') }}" alt="Logo The Framework" class="w-24 h-24 mx-auto">
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-light tracking-wider text-zinc-400 uppercase">The Framework</h1>
                    <h2 class="text-5xl font-bold mt-4">Payment Required</h2>
                    <p class="text-zinc-400 mt-4 text-lg">Segera selesaikan pembayaran untuk melanjutkan layanan</p>
                </div>
            </div>

            <!-- Payment Details Card -->
            <div class="bg-zinc-900/60 backdrop-blur-md border border-zinc-800 rounded-3xl p-10 space-y-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-base">
                    <div>
                        <p class="text-zinc-500">Nomor Invoice</p>
                        <p class="font-mono text-xl font-bold text-zinc-100">SPK/2024/WEB-001</p>
                    </div>
                    <div>
                        <p class="text-zinc-500">Jatuh Tempo</p>
                        <p class="text-cyan-400 text-xl font-bold">7 April 2025</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-zinc-500">Total Tagihan</p>
                        <p class="text-5xl font-bold text-zinc-100 mt-2">Rp2.580.000</p>
                    </div>
                    <div>
                        <p class="text-zinc-500">Status</p>
                        <p class="text-red-400 text-xl font-bold" id="daysOverdue">Terlambat</p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-4">
                    <div class="flex justify-between text-zinc-400">
                        <span class="font-medium">Sudah Dibayar • Rp545.000</span>
                        <span class="font-medium">Belum Dibayar • Rp2.035.000</span>
                    </div>
                    <div class="w-full bg-zinc-800 rounded-full h-4 overflow-hidden shadow-inner">
                        <div class="bg-gradient-to-r from-cyan-500 to-cyan-400 h-4 rounded-full shadow-lg" style="width: 21%; transition: width 1.5s ease;"></div>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <div class="text-center space-y-6">
                <a href="https://wa.me/6285730676143?text=Konfirmasi%20Pembayaran%20SPK/2024/WEB-001" target="_blank"
                   class="inline-flex items-center gap-4 px-12 py-6 bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-white rounded-2xl font-bold text-xl transition-all shadow-xl">
                    <i data-lucide="credit-card" class="w-7 h-7"></i>
                    Bayar Sekarang
                </a>
                <p class="text-zinc-400">
                    Sudah membayar? <a href="https://wa.me/6285730676143?text=Konfirmasi%20Pembayaran%20SPK/2024/WEB-001" target="_blank" class="text-cyan-400 hover:underline font-medium">Konfirmasi di sini</a>
                </p>
            </div>

            <!-- Contact -->
            <div class="bg-zinc-900/60 backdrop-blur-md border border-zinc-800 rounded-3xl p-10">
                <div class="flex items-center justify-center gap-4 mb-8">
                    <i data-lucide="help-circle" class="w-6 h-6 text-cyan-400"></i>
                    <h3 class="text-xl font-bold">Butuh Bantuan?</h3>
                </div>
                <div class="space-y-6 text-center">
                    <div>
                        <i data-lucide="mail" class="w-5 h-5 text-cyan-400 mb-2"></i>
                        <a href="mailto:chandratriantomo123@gmail.com" class="block text-cyan-400 hover:underline text-lg">chandratriantomo123@gmail.com</a>
                    </div>
                    <div>
                        <i data-lucide="phone" class="w-5 h-5 text-cyan-400 mb-2"></i>
                        <a href="tel:+6285730676143" class="block text-cyan-400 hover:underline text-lg">(+62) 857-3067-6143</a>
                    </div>
                </div>
                <p class="text-xs text-zinc-500 mt-8 pt-8 border-t border-zinc-800 text-center">
                    Keterlambatan pembayaran dapat mengakibatkan penangguhan layanan
                </p>
            </div>

            <!-- Footer -->
            <div class="text-center text-sm text-zinc-600">
                <p>&copy; 2025 The Framework. Created with ❤️ by 
                    <a href="https://github.com/Chandra2004" target="_blank" class="text-cyan-400 hover:underline">Chandra Tri Antomo</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        const dueDate = new Date('2025-04-07');
        const today = new Date();
        const diffDays = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
        const el = document.getElementById('daysOverdue');
        if (diffDays < 0) {
            el.textContent = `${Math.abs(diffDays)} Hari Terlambat`;
            el.classList.add('text-red-400');
        } else {
            el.textContent = `${diffDays} Hari Lagi`;
            el.classList.add('text-cyan-400');
        }
    </script>
</body>
</html>