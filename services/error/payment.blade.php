<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reminder</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-2xl w-full bg-white rounded-xl shadow-2xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="relative inline-block mb-6">
                    <div class="absolute -inset-2 bg-amber-100 rounded-full animate-pulse"></div>
                    <div class="relative bg-amber-500 p-4 rounded-full">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Pembayaran Tertunda</h1>
                <p class="text-gray-600">Segera selesaikan pembayaran untuk melanjutkan layanan</p>
            </div>

            <!-- Payment Details -->
            <div class="bg-amber-50 rounded-lg p-6 mb-8">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Nomor Invoice</p>
                        <p class="font-semibold">SPK/2024/WEB-001</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Jatuh Tempo</p>
                        <p class="font-semibold text-amber-600">7 April 2025</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Total Tagihan</p>
                        <p class="text-2xl font-bold text-gray-800">Rp2.580.000</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Keterlambatan</p>
                        <p class="font-semibold text-red-600" id="daysOverdue">0 Hari</p>
                    </div>
                    <div class="col-span-2">
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="bg-green-50 p-3 rounded-lg">
                                <p class="text-sm text-green-600">Sudah Dibayar</p>
                                <p class="font-bold text-green-700">Rp545.000</p>
                            </div>
                            <div class="bg-red-50 p-3 rounded-lg">
                                <p class="text-sm text-red-600">Belum Dibayar</p>
                                <p class="font-bold text-red-700">Rp2.035.000</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment CTA -->
            <div class="text-center">
                <a href="https://wa.me/6285730676143?text=Konfirmasi%20Pembayaran%20SPK/2024/WEB-001" 
                   target="_blank"
                   class="relative inline-flex items-center justify-center p-0.5 mb-2 me-2 overflow-hidden text-sm font-medium text-gray-900 rounded-lg group bg-gradient-to-br from-amber-400 to-amber-600 hover:from-amber-500 hover:to-amber-700">
                    <span class="relative px-8 py-2.5 transition-all ease-in duration-75 bg-white rounded-md group-hover:bg-opacity-0">
                        <span class="relative text-amber-600 group-hover:text-white">
                            Bayar Sekarang
                        </span>
                    </span>
                </a>
                
                <p class="text-sm text-gray-500 mt-4">
                    Sudah membayar? <a href="https://wa.me/6285730676143?text=Konfirmasi%20Pembayaran%20SPK/2024/WEB-001" 
                    target="_blank" class="text-amber-600 hover:underline">Konfirmasi Pembayaran</a>
                </p>
            </div>

            <!-- Footer Note -->
            <div class="mt-8 border-t pt-6 text-center">
                <div class="text-sm text-gray-500">
                    <p>Butuh bantuan? Hubungi kami:</p>
                    <p class="mt-1">
                        <a href="mailto:chandratriantomo123@gmail.com" class="font-medium hover:text-amber-600">chandratriantomo123@gmail.com</a><br>
                        <a href="tel:+6285730676143" class="font-medium hover:text-amber-600">(+62) 857-3067-6143</a>
                    </p>
                </div>
                <p class="text-xs text-gray-400 mt-4">
                    Keterlambatan pembayaran dapat mengakibatkan penangguhan layanan
                </p>
            </div>
        </div>
    </div>

    <script>
        // Menghitung hari keterlambatan
        const dueDate = new Date('2025-04-07');
        const today = new Date();
        const diffTime = dueDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        const daysOverdueElement = document.getElementById('daysOverdue');
        if(diffDays < 0) {
            daysOverdueElement.textContent = `${Math.abs(diffDays)} Hari Terlambat`;
        } else {
            daysOverdueElement.textContent = "0 Hari (Belum Jatuh Tempo)";
        }
    </script>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>
</body>
</html>