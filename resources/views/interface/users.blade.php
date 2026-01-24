@extends('template.layout')
@section('main-content')
    <!-- Main Content Wrapper with Premium Spacing -->
    <main class="min-h-screen pt-32 pb-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto flex flex-col relative z-0">

        <!-- HEADER SECTION -->
        <div class="flex flex-col md:flex-row justify-between items-end md:items-center mb-12 gap-6 animate-fade-in-down">
            <div>
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-white mb-3">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 to-blue-600">
                        {{ __('messages.user_management') }}
                    </span>
                </h1>
                <p class="text-gray-400 text-lg max-w-2xl">
                    {{ __('messages.user_management_desc') }}
                </p>
            </div>

            <!-- Add User Button (Premium Glow Effect) -->
            <button onclick="openModal()"
                class="group relative inline-flex items-center justify-center px-8 py-3.5 text-base font-bold text-white transition-all duration-200 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-xl hover:from-cyan-400 hover:to-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 shadow-lg shadow-cyan-500/30 hover:shadow-cyan-500/50 hover:-translate-y-0.5">
                <span
                    class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
                <span class="relative flex items-center gap-2">
                    <svg class="w-5 h-5 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ __('messages.add_new_member') }}
                </span>
            </button>
        </div>

        <!-- USERS GRID -->
        <div id="userGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($users as $user)
                <!-- User Card Item -->
                <a href="/users/information/{{ $user['uid'] }}"
                    class="group relative bg-gray-800/40 backdrop-blur-sm border border-gray-700/50 rounded-2xl p-6 hover:bg-gray-800/80 hover:border-cyan-500/50 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-cyan-500/10 flex items-center gap-5 overflow-hidden">

                    <!-- Decorative Background Gradient -->
                    <div
                        class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 rounded-full bg-cyan-500/10 blur-2xl group-hover:bg-cyan-500/20 transition-all">
                    </div>

                    <!-- Avatar / Initials -->
                    <div class="relative shrink-0">
                        @if (!empty($user['profile_picture']))
                            <img src="{{ url('/file/user-pictures/' . $user['profile_picture']) }}"
                                alt="{{ $user['name'] }}"
                                class="w-16 h-16 rounded-full object-cover border-2 border-gray-700 group-hover:border-cyan-400 transition-colors shadow-md">
                        @else
                            <div
                                class="w-16 h-16 rounded-full bg-gradient-to-br from-gray-700 to-gray-800 border-2 border-gray-600 group-hover:border-cyan-400 flex items-center justify-center text-xl font-bold text-gray-300 group-hover:text-cyan-400 transition-all shadow-md">
                                {{ strtoupper(substr($user['name'], 0, 1)) }}
                            </div>
                        @endif
                        <!-- Status Indicator (Dummy Active) -->
                        <div class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-gray-800 rounded-full">
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="relative z-10 min-w-0 flex-1">
                        <h3 class="text-xl font-bold text-gray-100 truncate group-hover:text-cyan-400 transition-colors">
                            {{ $user['name'] }}
                        </h3>
                        <p class="text-sm text-gray-400 truncate mb-1">{{ $user['email'] ?? 'No Email' }}</p>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            <span>{{ __('messages.joined') }} {{ date('M d, Y', strtotime($user['created_at'])) }}</span>
                        </div>
                    </div>

                    <!-- Arrow Icon -->
                    <div
                        class="text-gray-600 group-hover:text-cyan-400 transition-colors transform group-hover:translate-x-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            @empty
                <!-- Empty State -->
                <div id="emptyState"
                    class="col-span-full py-20 flex flex-col items-center justify-center text-center border-2 border-dashed border-gray-700 rounded-2xl bg-gray-800/20">
                    <div class="bg-gray-800 p-4 rounded-full mb-4">
                        <svg class="w-12 h-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">{{ __('messages.no_members') }}</h3>
                    <p class="text-gray-400 mb-6 max-w-sm">{{ __('messages.no_members_desc') }}</p>
                    <button onclick="openModal()"
                        class="text-cyan-400 font-semibold hover:text-cyan-300 hover:underline">{{ __('messages.add_first_member') }}</button>
                </div>
            @endforelse
        </div>
    </main>

    <!-- TOAST NOTIFICATION CONTAINER (BOTTOM-RIGHT) -->
    <!-- Positioned at Bottom-Right to avoid header conflict safely -->
    <div id="toast-container" class="fixed bottom-8 right-8 z-[60] flex flex-col gap-3 pointer-events-none"></div>

    <!-- MODAL (Custom Implementation for Robustness) -->
    <!-- z-50 to be above content, but below toast if needed -->
    <div id="customModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div id="modalBackdrop" class="fixed inset-0 bg-gray-900/90 backdrop-blur-sm transition-opacity opacity-0"
            aria-hidden="true"></div>

        <!-- Modal Panel -->
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div id="modalPanel"
                    class="relative transform overflow-hidden rounded-2xl bg-gray-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-700 opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <!-- Modal Header -->
                    <div
                        class="bg-gradient-to-r from-gray-800 to-gray-900 border-b border-gray-700 px-6 py-5 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ __('messages.create_user') }}</h3>
                            <p class="text-sm text-gray-400 mt-1">{{ __('messages.create_user_desc') }}</p>
                        </div>
                        <button type="button" onclick="closeModal()"
                            class="text-gray-400 hover:text-white transition-colors bg-gray-700/50 hover:bg-gray-700 rounded-lg p-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form id="createUserForm" class="p-6 space-y-5" enctype="multipart/form-data">
                        @csrf

                        <!-- Name Field -->
                        <div>
                            <label for="name"
                                class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.full_name') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-cyan-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                <input type="text" name="name" id="name" required
                                    class="block w-full rounded-xl border-gray-600 bg-gray-700/50 pl-10 py-3 text-white placeholder-gray-400 focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm transition-all shadow-sm"
                                    placeholder="e.g. John Doe">
                            </div>
                        </div>

                        <!-- Email Field -->
                        <div>
                            <label for="email"
                                class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.email_address') }}</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-cyan-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </span>
                                <input type="email" name="email" id="email" required
                                    class="block w-full rounded-xl border-gray-600 bg-gray-700/50 pl-10 py-3 text-white placeholder-gray-400 focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm transition-all shadow-sm"
                                    placeholder="john@example.com">
                            </div>
                        </div>

                        <!-- File Upload Area -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-300 mb-2">{{ __('messages.profile_picture') }}</label>
                            <div class="mt-1 flex justify-center rounded-xl border-2 border-dashed border-gray-600 px-6 pt-5 pb-6 hover:border-cyan-500 hover:bg-gray-700/30 transition-all cursor-pointer"
                                onclick="document.getElementById('profile_picture').click()">
                                <div class="space-y-1 text-center">
                                    <!-- Preview Image Container -->
                                    <div id="previewContainer" class="hidden mb-3">
                                        <img id="previewImage" src="#" alt="Preview"
                                            class="mx-auto h-24 w-24 rounded-full object-cover border-2 border-cyan-500">
                                        <p id="fileName" class="text-sm text-cyan-400 mt-2 font-medium"></p>
                                    </div>
                                    <!-- Default Icon -->
                                    <div id="uploadIcon">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                            viewBox="0 0 48 48" aria-hidden="true">
                                            <path
                                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-400 justify-center mt-2">
                                            <span
                                                class="relative cursor-pointer rounded-md font-medium text-cyan-500 hover:text-cyan-400 focus-within:outline-none">
                                                <span>{{ __('messages.upload_file') }}</span>
                                            </span>
                                            <p class="pl-1">{{ __('messages.drag_drop') }}</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG up to 2MB</p>
                                    </div>
                                    <input id="profile_picture" name="profile_picture" type="file" class="hidden"
                                        accept="image/*">
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="mt-8 flex gap-3">
                            <button type="button" onclick="closeModal()"
                                class="w-full rounded-xl bg-gray-700 px-4 py-3 text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-gray-600 hover:bg-gray-600 transition-all">{{ __('messages.cancel') }}</button>
                            <button type="submit" id="submitBtn"
                                class="w-full rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg hover:from-cyan-500 hover:to-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-600 transition-all flex justify-center items-center gap-2">
                                <span>{{ __('messages.save_member') }}</span>
                                <!-- Spinner -->
                                <svg class="hidden w-5 h-5 text-white animate-spin loading-spinner"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC (Robust & Clean) -->
    <script>
        // --- 1. MODAL SYSTEM (Native & Manual for Stability) ---
        const modal = document.getElementById('customModal');
        const modalBackdrop = document.getElementById('modalBackdrop');
        const modalPanel = document.getElementById('modalPanel');
        const body = document.body;

        function openModal() {
            modal.classList.remove('hidden');
            // Animate In sequence
            setTimeout(() => {
                modalBackdrop.classList.remove('opacity-0');
                modalPanel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
                modalPanel.classList.add('opacity-100', 'translate-y-0', 'sm:scale-100');
            }, 10); // small delay for CSS transition trigger
            body.classList.add('overflow-hidden'); // Prevent background scroll
        }

        function closeModal() {
            // Animate Out sequence
            modalBackdrop.classList.add('opacity-0');
            modalPanel.classList.remove('opacity-100', 'translate-y-0', 'sm:scale-100');
            modalPanel.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');

            setTimeout(() => {
                modal.classList.add('hidden');
                body.classList.remove('overflow-hidden');
                resetFormVisuals();
            }, 300); // Wait for transition duration (300ms)
        }

        // Close on backdrop click
        modalBackdrop.addEventListener('click', closeModal);

        function resetFormVisuals() {
            document.getElementById('createUserForm').reset();
            document.getElementById('previewContainer').classList.add('hidden');
            document.getElementById('uploadIcon').classList.remove('hidden');
            document.getElementById('profile_picture').value = ''; // clear file input
        }

        // --- 2. FILE PREVIEW ---
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.getElementById('previewContainer');
            const previewImage = document.getElementById('previewImage');
            const fileName = document.getElementById('fileName');
            const uploadIcon = document.getElementById('uploadIcon');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    fileName.textContent = file.name;
                    previewContainer.classList.remove('hidden');
                    uploadIcon.classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        });

        // --- 3. TOAST NOTIFICATION SYSTEM (MATCHING NATIVE DESIGN) ---
        function showNotification(type, message) {
            const container = document.getElementById('toast-container');
            const isSuccess = type === 'success';

            // Design Config based on notification.blade.php
            const iconBg = isSuccess ? 'bg-cyan-400/20' : 'bg-red-500/20';
            const iconColor = isSuccess ? 'text-cyan-400' : 'text-red-500';
            const iconSvg = isSuccess ?
                '<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />' :
                '<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z" />';

            const toast = document.createElement('div');
            // Exact classes from notification.blade.php
            toast.className =
                `flex items-center w-full max-w-xs p-4 mb-4 text-gray-300 bg-gray-900/90 backdrop-blur-lg border border-gray-800 rounded-lg shadow-sm transform transition-all duration-300 ease-in-out translate-y-4 opacity-0 pointer-events-auto`;

            toast.innerHTML = `
                    <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 ${iconColor} ${iconBg} rounded-lg">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            ${iconSvg}
                        </svg>
                        <span class="sr-only">${isSuccess ? 'Success' : 'Error'}</span>
                    </div>
                    <div class="ms-3 text-sm font-normal">
                        ${message}
                    </div>
                    <button type="button" class="ms-auto -mx-1.5 -my-1.5 text-gray-400 hover:text-cyan-400 rounded-lg p-1.5 hover:bg-gray-800 inline-flex items-center justify-center h-8 w-8" onclick="this.parentElement.remove()">
                        <span class="sr-only">Close</span>
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                    </button>
                `;

            container.appendChild(toast);

            // Animate In (translate-y-0 opacity-100)
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-4', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            });

            // Auto Remove (5s default like blade fallback)
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-4', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // --- 4. FORM HANDLER (Fetch Logic) ---
        const form = document.getElementById('createUserForm');
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const spinner = submitBtn.querySelector('.loading-spinner');
            const btnText = submitBtn.querySelector('span');

            // UI Loading State
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            spinner.classList.remove('hidden');
            btnText.textContent = 'Processing...';

            const formData = new FormData(this);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch('/api/users/create', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'include',
                    body: formData
                });

                // Safe parsing
                const contentType = response.headers.get("content-type");
                let result;
                if (contentType && contentType.includes("application/json")) {
                    result = await response.json();
                } else {
                    const text = await response.text();
                    console.error("Server Response (Not JSON):", text);
                    throw new Error("Server returned an invalid response.");
                }

                if (response.ok) {
                    showNotification('success', result.message || 'User successfully created!');
                    closeModal(); // Close modal immediately on success

                    // SPA Update: Add new card to grid
                    if (result.data) {
                        const newUser = result.data;
                        const grid = document.getElementById('userGrid');
                        const emptyState = document.getElementById('emptyState');
                        if (emptyState) emptyState.remove();

                        const profileImg = newUser.profile_picture ?
                            `<img src="/file/user-pictures/${newUser.profile_picture}" class="w-16 h-16 rounded-full object-cover border-2 border-gray-700 group-hover:border-cyan-400 transition-colors shadow-md">` :
                            `<div class="w-16 h-16 rounded-full bg-gradient-to-br from-gray-700 to-gray-800 border-2 border-gray-600 group-hover:border-cyan-400 flex items-center justify-center text-xl font-bold text-gray-300 group-hover:text-cyan-400 transition-all shadow-md">${newUser.name.charAt(0).toUpperCase()}</div>`;

                        const newCard = `
                                <a href="/users/information/${newUser.uid}" class="group relative bg-gray-800/40 backdrop-blur-sm border border-gray-700/50 rounded-2xl p-6 hover:bg-gray-800/80 hover:border-cyan-500/50 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-cyan-500/10 flex items-center gap-5 overflow-hidden animate-fade-in-down">
                                     <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 rounded-full bg-cyan-500/10 blur-2xl group-hover:bg-cyan-500/20 transition-all"></div>
                                     <div class="relative shrink-0">
                                        ${profileImg}
                                        <div class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-gray-800 rounded-full"></div>
                                     </div>
                                     <div class="relative z-10 min-w-0 flex-1">
                                        <h3 class="text-xl font-bold text-gray-100 truncate group-hover:text-cyan-400 transition-colors">${newUser.name}</h3>
                                        <p class="text-sm text-gray-400 truncate mb-1">${newUser.email || 'No Email'}</p>
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <span>Joined Just now</span>
                                        </div>
                                     </div>
                                      <div class="text-gray-600 group-hover:text-cyan-400 transition-colors transform group-hover:translate-x-1">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                </a>
                                `;
                        // Insert at the beginning
                        grid.insertAdjacentHTML('afterbegin', newCard);
                    }
                } else {
                    showNotification('error', result.message || 'Failed to create user.');
                }

            } catch (error) {
                console.error(error);
                showNotification('error', 'Connection Error. Please try again.');
            } finally {
                // UI Reset
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                spinner.classList.add('hidden');
                btnText.textContent = 'Save Member';
            }
        });
    </script>
@endsection
