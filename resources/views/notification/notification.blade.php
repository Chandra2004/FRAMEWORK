<div class="fixed top-0 right-0 z-[9999] flex flex-col items-end p-4 space-y-4">
    @if (isset($notification['status']) && $notification['status'] === 'error')
        <div id="alert-2"
            class="flex items-center w-full max-w-xs p-4 mb-4 text-gray-300 bg-gray-900/90 backdrop-blur-lg border border-gray-800 rounded-lg shadow-sm opacity-0 transform translate-y-4 transition-all duration-300 ease-in-out"
            role="alert" data-notification-type="error">
            <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 text-red-500 bg-red-500/20 rounded-lg">
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z" />
                </svg>
                <span class="sr-only">Error</span>
            </div>
            <div class="ms-3 text-sm font-normal">
                {{ $notification['message'] }}
            </div>
            <button type="button"
                class="ms-auto -mx-1.5 -my-1.5 text-gray-400 hover:text-cyan-400 rounded-lg p-1.5 hover:bg-gray-800 inline-flex items-center justify-center h-8 w-8"
                data-dismiss-target aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    @endif

    @if (isset($notification['status']) && $notification['status'] === 'success')
        <div id="alert-3"
            class="flex items-center w-full max-w-xs p-4 mb-4 text-gray-300 bg-gray-900/90 backdrop-blur-lg border border-gray-800 rounded-lg shadow-sm opacity-0 transform translate-y-4 transition-all duration-300 ease-in-out"
            role="alert" data-notification-type="success">
            <div
                class="inline-flex items-center justify-center shrink-0 w-8 h-8 text-cyan-400 bg-cyan-400/20 rounded-lg">
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                </svg>
                <span class="sr-only">Success</span>
            </div>
            <div class="ms-3 text-sm font-normal">
                {{ $notification['message'] }}
            </div>
            <button type="button"
                class="ms-auto -mx-1.5 -my-1.5 text-gray-400 hover:text-cyan-400 rounded-lg p-1.5 hover:bg-gray-800 inline-flex items-center justify-center h-8 w-8"
                data-dismiss-target aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    @endif

    @if (isset($notification['status']) && $notification['status'] === 'warning')
        <div id="alert-4" 
            class="flex items-center w-full max-w-xs p-4 mb-4 text-gray-300 bg-gray-900/90 backdrop-blur-lg border border-gray-800 rounded-lg shadow-sm opacity-0 transform translate-y-4 transition-all duration-300 ease-in-out"
            role="alert" data-notification-type="warning">
            <div
                class="inline-flex items-center justify-center shrink-0 w-8 h-8 text-orange-500 bg-orange-500/20 rounded-lg">
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM10 15a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1-4a1 1 0 0 1-2 0V6a1 1 0 0 1 2 0v5Z" />
                </svg>
                <span class="sr-only">Warning</span>
            </div>
            <div class="ms-3 text-sm font-normal">
                {{ $notification['message'] }}
            </div>
            <button type="button"
                class="ms-auto -mx-1.5 -my-1.5 text-gray-400 hover:text-cyan-400 rounded-lg p-1.5 hover:bg-gray-800 inline-flex items-center justify-center h-8 w-8"
                data-dismiss-target aria-label="Close">
                <span class="sr-only">Close</span>
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    @endif

    @if (isset($notification['status']))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ambil notifikasi yang sesuai berdasarkan status dari PHP
            const notificationStatus = '{{ $notification['status'] }}';
            const activeToast = document.querySelector(`[data-notification-type="${notificationStatus}"]`);

            if (activeToast) {
                // Ambil durasi dari data PHP, default ke 10000ms (10 detik) jika tidak ada.
                const toastDuration = {{ isset($notification['duration']) ? (int)$notification['duration'] : 10000 }};
                
                // Fungsi untuk menampilkan toast
                const showToast = () => {
                    activeToast.classList.remove('opacity-0', 'translate-y-4');
                    activeToast.classList.add('opacity-100', 'translate-y-0');
                };

                // Fungsi untuk menyembunyikan toast
                const hideToast = () => {
                    activeToast.classList.remove('opacity-100', 'translate-y-0');
                    activeToast.classList.add('opacity-0', 'translate-y-4');
                    activeToast.addEventListener('transitionend', () => {
                        activeToast.remove();
                    }, { once: true });
                };

                // Setup tombol close manual
                const closeButton = activeToast.querySelector('[data-dismiss-target]');
                if (closeButton) {
                    closeButton.addEventListener('click', () => {
                        // Hapus timeout jika user menutup manual
                        clearTimeout(timer);
                        hideToast();
                    });
                }

                // Tampilkan toast
                showToast();

                // Set timer untuk menyembunyikan toast secara otomatis
                const timer = setTimeout(hideToast, toastDuration);
            }
        });
    </script>
    @endif
</div>
