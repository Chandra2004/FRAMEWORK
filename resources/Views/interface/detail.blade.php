@extends('template.layout')
@section('main-content')
    <main class="pt-32 pb-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="max-w-3xl mx-auto">
            <div class="mb-8 flex items-center justify-between">
                <a href="/users" class="text-cyan-400 hover:text-cyan-300 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Users
                </a>

                <!-- (Optional) Add User via Modal from here is redundant but kept for layout consistency if needed -->
            </div>

            <!-- User Card -->
            <div class="bg-gray-800/50 p-8 rounded-2xl border border-gray-700/50">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                        User Details
                    </h1>
                    <div class="flex gap-2">
                        <span class="bg-cyan-400/10 text-cyan-400 px-4 py-2 rounded-full text-sm">
                            ID: {{ $user['id'] }}
                        </span>
                        <span class="bg-cyan-400/10 text-cyan-400 px-4 py-2 rounded-full text-sm">
                            UID: {{ $user['uid'] }}
                        </span>
                    </div>
                </div>

                <!-- AJAX Update Form -->
                <form id="updateUserForm" class="space-y-6" enctype="multipart/form-data">
                    <h3
                        class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent mb-6">
                        Edit User
                    </h3>

                    <!-- Name Input -->
                    <div class="relative group">
                        <label for="name" class="block text-sm font-medium text-gray-400 mb-2">Name</label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-cyan-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input type="text" name="name" id="name" value="{{ $user['name'] }}"
                                class="bg-gray-700/50 border border-gray-600/50 text-white text-sm rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 block w-full pl-10 p-3.5 hover:border-cyan-400/50 transition-all"
                                placeholder="Full Name" required>
                        </div>
                    </div>

                    <!-- Email Input -->
                    <div class="relative group">
                        <label for="email" class="block text-sm font-medium text-gray-400 mb-2">Email</label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-cyan-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="email" name="email" id="email" value="{{ $user['email'] }}"
                                class="bg-gray-700/50 border border-gray-600/50 text-white text-sm rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 block w-full pl-10 p-3.5 hover:border-cyan-400/50 transition-all"
                                placeholder="name@company.com" required>
                        </div>
                    </div>

                    <!-- Profile Picture Upload -->
                    <div class="relative group">
                        <label for="profile_picture" class="block text-sm font-medium text-gray-400 mb-2">Profile
                            Picture</label>
                        <div class="flex items-center justify-center w-full">
                            <label
                                class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-600/50 rounded-lg cursor-pointer bg-gray-700/50 hover:border-cyan-400/50 hover:bg-gray-700/70 transition-all">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center">
                                    <svg class="w-8 h-8 mb-3 text-cyan-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-sm text-gray-400">
                                        <span class="font-semibold text-cyan-400">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG (MAX. 2MB)</p>
                                </div>
                                <input type="file" name="profile_picture" id="profile_picture" class="hidden"
                                    accept="image/jpeg, image/png">
                            </label>
                        </div>
                        <!-- Image Preview -->
                        <div id="imagePreview" class="mt-4 hidden flex flex-col gap-2">
                            <img id="previewImage"
                                class="w-full max-h-48 object-contain rounded-lg border border-cyan-400/50 bg-gray-700/50"
                                src="#" alt="Image Preview">
                            <p id="fileName" class="text-sm text-cyan-400 text-center truncate"></p>
                        </div>
                    </div>

                    <!-- Delete Profile Picture Checkbox -->
                    <div class="flex items-center mt-4">
                        <input type="checkbox" name="delete_profile_picture" id="delete_profile_picture"
                            class="w-5 h-5 text-cyan-500 bg-gray-700 border-gray-600 rounded focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <label for="delete_profile_picture" class="ml-2 text-gray-400 text-sm">Delete profile
                            picture</label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="updateBtn"
                        class="w-full py-3.5 px-6 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all transform hover:scale-[1.02] shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/30 flex justify-center items-center">
                        <span>Update User</span>
                        <svg class="w-4 h-4 ml-2 inline-block loading-hide-update" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <!-- Loading Spinner -->
                        <svg class="w-5 h-5 ml-2 animate-spin hidden loading-show-update" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </button>
                </form>

                <!-- User Info -->
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Aksi</p>
                            <!-- AJAX Delete Form -->
                            <form id="deleteUserForm" method="POST">
                                <button type="submit" id="deleteBtn"
                                    class="flex gap-2 py-3.5 px-6 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-semibold rounded-lg transition-all transform hover:scale-[1.02] shadow-lg shadow-orange-500/20 hover:shadow-orange-500/30 w-full justify-center items-center">
                                    <svg class="w-6 h-6 text-white loading-hide-delete" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                        viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z" />
                                    </svg>
                                    <span class="loading-hide-delete">Hapus User</span>

                                    <!-- Loading Spinner -->
                                    <svg class="w-5 h-5 animate-spin hidden loading-show-delete"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Username</p>
                            <p class="text-gray-100 font-medium">{{ $user['name'] }}</p>
                        </div>
                        <div>
                            <ul>
                                <li>
                                    <p class="text-gray-400 text-sm mb-1">Account Created</p>
                                    <p class="text-gray-100 font-medium">
                                        {{ date('H:i d-m-Y', strtotime($user['created_at'])) }}
                                    </p>
                                </li>
                                <li>
                                    <p class="text-gray-400 text-sm mb-1">Account Update</p>
                                    <p class="text-gray-100 font-medium">
                                        {{ date('H:i d-m-Y', strtotime($user['updated_at'])) }}
                                    </p>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Profile Picture</p>
                            @php
                                $profileUrl = $user['profile_picture']
                                    ? url('/file/user-pictures/' . $user['profile_picture'])
                                    : url('/file/dummy/dummy.webp');
                            @endphp
                            <a href="{{ $profileUrl }}" target="_blank" id="currentProfileLink">
                                <img src="{{ $profileUrl }}" alt="{{ $user['name'] }}" loading="lazy"
                                    id="currentProfileImage"
                                    class="h-24 w-24 object-cover rounded-xl border border-gray-700">
                            </a>
                        </div>
                    </div>

                    <!-- Security Badge -->
                    <div class="mt-8 p-4 bg-gray-900/50 rounded-lg border border-cyan-400/20 flex items-center gap-4">
                        <div class="bg-cyan-400/10 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-gray-100 font-medium mb-1">Security Note</h3>
                            <p class="text-gray-400 text-sm">
                                Passwords are securely hashed using bcrypt algorithm and cannot be retrieved in plain text.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-24 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <script>
        // --- TOAST NOTIFICATION SYSTEM (MATCHING NATIVE DESIGN) ---
        function showNotification(type, message) {
            const container = document.getElementById('toast-container');
            const isSuccess = type === 'success';

            // Design Config based on notification.blade.php
            const iconBg = isSuccess ? 'bg-cyan-400/20' : 'bg-red-500/20';
            const iconColor = isSuccess ? 'text-cyan-400' : 'text-red-500';
            const iconSvg = isSuccess
                ? '<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />'
                : '<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z" />';

            const toast = document.createElement('div');
            // Exact classes from notification.blade.php
            toast.className = `flex items-center w-full max-w-xs p-4 mb-4 text-gray-300 bg-gray-900/90 backdrop-blur-lg border border-gray-800 rounded-lg shadow-sm transform transition-all duration-300 ease-in-out translate-y-4 opacity-0 pointer-events-auto`;

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

        // --- FILE PREVIEW ---
        const fileInput = document.getElementById('profile_picture');
        if (fileInput) {
            fileInput.addEventListener('change', function (e) {
                const file = this.files[0];
                const imagePreview = document.getElementById('imagePreview');
                const previewImage = document.getElementById('previewImage');
                const fileNameDisplay = document.getElementById('fileName');
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImage.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                        fileNameDisplay.textContent = file.name;
                    };
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.classList.add('hidden');
                    fileNameDisplay.textContent = '';
                }
            });
        }

        // --- AJAX HANDLERS ---
        const userUid = "{{ $user['uid'] }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // 1. UPDATE USER
        const updateForm = document.getElementById('updateUserForm');
        if (updateForm) {
            updateForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                // NO CONFIRM ALERT - DIRECT ACTION
                const updateBtn = document.getElementById('updateBtn');
                const loadShowUpd = document.querySelector('.loading-show-update');
                const loadHideUpd = document.querySelector('.loading-hide-update');

                updateBtn.disabled = true;
                loadShowUpd.classList.remove('hidden');
                loadHideUpd.classList.add('hidden');

                const formData = new FormData(this);

                try {
                    const response = await fetch(`/api/users/update/${userUid}`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        credentials: 'include',
                        body: formData
                    });

                    // Safe JSON Parse
                    const contentType = response.headers.get("content-type");
                    let result;
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        result = await response.json();
                    } else {
                        const text = await response.text();
                        console.error("Non-JSON Update Response:", text);
                        throw new Error("Server Error (Invalid response)");
                    }

                    if (response.ok) {
                        showNotification('success', result.message || 'User updated successfully!');

                        // --- UPDATE DOM ELEMENTS REAL-TIME ---
                        if (result.data) {
                            const updatedUser = result.data;
                            
                            // 1. Update Profile Image (Graceful Preloading)
                            if (updatedUser.profile_picture) {
                                const baseImgUrl = `/file/user-pictures/${updatedUser.profile_picture}`;
                                const timestamp = new Date().getTime();
                                const fullImgUrl = `${baseImgUrl}?t=${timestamp}`;

                                // Preload image first to avoid broken icon
                                const tempImg = new Image();
                                tempImg.src = fullImgUrl;
                                
                                // Visual Loading State
                                const imgEl = document.getElementById('currentProfileImage');
                                const linkEl = document.getElementById('currentProfileLink');
                                if(imgEl) {
                                    imgEl.classList.add('opacity-50', 'blur-sm', 'transition-all', 'duration-300');
                                }

                                tempImg.onload = function() {
                                    if(imgEl) {
                                        imgEl.src = fullImgUrl;
                                        // Remove loading state
                                        setTimeout(() => {
                                            imgEl.classList.remove('opacity-50', 'blur-sm');
                                        }, 100);
                                    } 
                                    if(linkEl) linkEl.href = fullImgUrl;
                                };
                                
                                tempImg.onerror = function() {
                                    console.warn('Image not ready yet, retrying in 1s...');
                                    setTimeout(() => {
                                        // Retry update directly
                                        if(imgEl) {
                                            imgEl.src = fullImgUrl;
                                            imgEl.classList.remove('opacity-50', 'blur-sm');
                                        }
                                    }, 1000);
                                };
                            } else {
                                // Revert to dummy if deleted
                                const dummyUrl = `/file/dummy/dummy.webp`;
                                const imgEl = document.getElementById('currentProfileImage');
                                const linkEl = document.getElementById('currentProfileLink');
                                if(imgEl) imgEl.src = dummyUrl;
                                if(linkEl) linkEl.href = dummyUrl;
                            }

                            // 2. Update Name Display
                            const nameInput = document.getElementById('name');
                            if(nameInput && nameInput.value !== updatedUser.name) nameInput.value = updatedUser.name;
                        }
                        
                        // Clear file input preview
                        document.getElementById('profile_picture').value = '';
                        document.getElementById('imagePreview').classList.add('hidden');
                        document.getElementById('fileName').textContent = '';

                    } else {
                        showNotification('error', result.message || 'Update failed');
                    }
                } catch (error) {
                    console.error(error);
                    showNotification('error', error.message || 'Connection Error');
                } finally {
                    updateBtn.disabled = false;
                    loadShowUpd.classList.add('hidden');
                    loadHideUpd.classList.remove('hidden');
                }
            });
        }

        // 2. DELETE USER (2-Step Confirmation Button)
        const deleteForm = document.getElementById('deleteUserForm');
        const deleteBtn = document.getElementById('deleteBtn');
        let deleteConfirmMode = false;

        if (deleteForm) {
            deleteForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                // 2-Step Logic
                if (!deleteConfirmMode) {
                    // Step 1: Ask for confirmation on the button itself
                    deleteConfirmMode = true;
                    const originalContent = deleteBtn.innerHTML;

                    // Change Button Appearance
                    deleteBtn.innerHTML = `<span class="font-bold">Confirm Delete?</span>`;
                    deleteBtn.classList.remove('from-orange-500', 'to-red-600');
                    deleteBtn.classList.add('from-red-600', 'to-red-800', 'animate-pulse');

                    // Reset after 3 seconds if not clicked
                    setTimeout(() => {
                        if (deleteConfirmMode) { // Only reset if still in confirm mode (and not loading)
                            deleteConfirmMode = false;
                            deleteBtn.innerHTML = originalContent;
                            deleteBtn.classList.add('from-orange-500', 'to-red-600');
                            deleteBtn.classList.remove('from-red-600', 'to-red-800', 'animate-pulse');
                        }
                    }, 3000);
                    return;
                }

                // Step 2: EXECUTE DELETE
                deleteConfirmMode = false; // Reset mode prevents double submit race conditions

                const loadShowDel = document.querySelector('.loading-show-delete');
                const loadHideDel = document.querySelectorAll('.loading-hide-delete');

                deleteBtn.disabled = true;
                deleteBtn.classList.add('opacity-75');
                // Revert button text for loading state
                deleteBtn.classList.add('from-orange-500', 'to-red-600');
                deleteBtn.classList.remove('from-red-600', 'to-red-800', 'animate-pulse');

                loadShowDel.classList.remove('hidden');
                // Hide existing content inside button including our temporary content
                Array.from(deleteBtn.children).forEach(child => {
                    if (!child.classList.contains('loading-show-delete')) child.classList.add('hidden');
                });


                try {
                    const response = await fetch(`/api/users/delete/${userUid}`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        credentials: 'include'
                    });

                    // Safe JSON Parse
                    const contentType = response.headers.get("content-type");
                    let result;
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        result = await response.json();
                    } else {
                        const text = await response.text();
                        console.error("Non-JSON Delete Response:", text);
                        throw new Error("Server Error (Invalid response)");
                    }

                    if (response.ok) {
                        showNotification('success', 'User Deleted. Redirecting...');
                        setTimeout(() => {
                            window.location.href = '/users';
                        }, 1500);
                    } else {
                        showNotification('error', result.message || 'Delete failed');
                        // Restore UI
                        deleteBtn.disabled = false;
                        deleteBtn.classList.remove('opacity-75');
                        loadShowDel.classList.add('hidden');
                        // Restore original button content (hardcoded reset to be safe)
                        deleteBtn.innerHTML = `
                                <svg class="w-6 h-6 text-white loading-hide-delete" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                </svg>
                                <span class="loading-hide-delete">Hapus User</span>
                                <svg class="w-5 h-5 animate-spin hidden loading-show-delete" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            `;
                    }
                } catch (error) {
                    console.error(error);
                    showNotification('error', error.message || 'Connection Error');
                    deleteBtn.disabled = false;
                    // Restore UI (Simpler reset)
                    location.reload(); // Safety fallback if UI state gets messy
                }
            });
        }
    </script>
@endsection