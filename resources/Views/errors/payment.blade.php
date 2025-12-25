<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Required | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .animate-fade-in { animation: fadeIn 0.6s ease-out; }
        .animate-pulse-slow { animation: pulse 2s ease-in-out infinite; }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-950 via-amber-950/20 to-slate-950 min-h-screen text-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="max-w-2xl w-full space-y-8 animate-fade-in">
            <!-- Header -->
            <div class="text-center space-y-4">
                <div class="flex justify-center">
                    <div class="relative">
                        <div class="absolute inset-0 bg-amber-500/20 rounded-full blur-2xl animate-pulse-slow"></div>
                        <div class="relative bg-slate-800/50 border border-amber-500/20 rounded-full p-6">
                            <i data-lucide="credit-card" class="w-16 h-16 text-amber-500"></i>
                        </div>
                    </div>
                </div>
                <h1 class="text-4xl sm:text-5xl font-bold bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">
                    Payment Required
                </h1>
                <p class="text-lg text-slate-300">
                    Segera selesaikan pembayaran untuk melanjutkan layanan
                </p>
            </div>

            <!-- Payment Details Card -->
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6 space-y-6">
                <!-- Invoice Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Nomor Invoice</p>
                        <p class="text-slate-200 font-semibold font-mono">SPK/2024/WEB-001</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Jatuh Tempo</p>
                        <p class="text-amber-400 font-semibold">7 April 2025</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-slate-400 mb-1">Total Tagihan</p>
                        <p class="text-3xl font-bold text-slate-200">Rp2.580.000</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Keterlambatan</p>
                        <p class="text-red-400 font-semibold" id="daysOverdue">0 Hari</p>
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-700/50">
                    <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-400"></i>
                            <p class="text-sm text-green-400 font-medium">Sudah Dibayar</p>
                        </div>
                        <p class="text-xl font-bold text-green-300">Rp545.000</p>
                    </div>
                    <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-red-400"></i>
                            <p class="text-sm text-red-400 font-medium">Belum Dibayar</p>
                        </div>
                        <p class="text-xl font-bold text-red-300">Rp2.035.000</p>
                    </div>
                </div>
            </div>

            <!-- Payment CTA -->
            <div class="text-center space-y-4">
                <a href="https://wa.me/6285730676143?text=Konfirmasi%20Pembayaran%20SPK/2024/WEB-001" target="_blank"
                   class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white rounded-lg font-semibold transition-all shadow-lg shadow-amber-500/20">
                    <i data-lucide="credit-card" class="w-5 h-5"></i>
                    Bayar Sekarang
                </a>
                <p class="text-sm text-slate-400">
                    Sudah membayar? 
                    <a href="https://wa.me/6285730676143?text=Konfirmasi%20Pembayaran%20SPK/2024/WEB-001" target="_blank" 
                       class="text-amber-400 hover:underline">Konfirmasi Pembayaran</a>
                </p>
            </div>

            <!-- Contact Info -->
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <i data-lucide="help-circle" class="w-5 h-5 text-cyan-400"></i>
                    <h3 class="text-lg font-semibold text-slate-200">Butuh Bantuan?</h3>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-3">
                        <i data-lucide="mail" class="w-4 h-4 text-cyan-400"></i>
                        <a href="mailto:chandratriantomo123@gmail.com" class="text-cyan-400 hover:underline">
                            chandratriantomo123@gmail.com
                        </a>
                    </div>
                    <div class="flex items-center gap-3">
                        <i data-lucide="phone" class="w-4 h-4 text-cyan-400"></i>
                        <a href="tel:+6285730676143" class="text-cyan-400 hover:underline">
                            (+62) 857-3067-6143
                        </a>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-4 pt-4 border-t border-slate-700/50">
                    Keterlambatan pembayaran dapat mengakibatkan penangguhan layanan
                </p>
            </div>

            <!-- Footer -->
            <div class="text-center pt-4 border-t border-slate-800/50">
                <p class="text-sm text-slate-500">
                    &copy; {{ date('Y') }} The Framework. Created with ❤️ by 
                    <a href="https://github.com/Chandra2004" target="_blank" class="text-cyan-400 hover:underline">Chandra Tri Antomo</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // Calculate days overdue
        const dueDate = new Date('2025-04-07');
        const today = new Date();
        const diffTime = dueDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        const daysOverdueElement = document.getElementById('daysOverdue');
        if (diffDays < 0) {
            daysOverdueElement.textContent = `${Math.abs(diffDays)} Hari Terlambat`;
        } else {
            daysOverdueElement.textContent = "0 Hari (Belum Jatuh Tempo)";
        }
    </script>
</body>

</html>