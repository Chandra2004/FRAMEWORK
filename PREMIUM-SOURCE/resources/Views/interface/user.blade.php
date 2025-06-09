<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐦</text></svg>">

    <!-- Primary Meta Tags -->
    <title>User Management - THE FRAMEWORK</title>
    <meta name="description" content="User management interface for THE FRAMEWORK framework">
    <meta name="keywords" content="User Management, PHP Framework, MVC, Database">
    <meta name="author" content="Chandra Tri Antomo">

    <!-- Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>

    <style>
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: rgb(17 24 39 / 0.8);
        }

        ::-webkit-scrollbar-thumb {
            background: rgb(17 24 39);
        }

        @keyframes modalEnter {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        input:focus,
        button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.2);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-gray-900/20 backdrop-blur-lg border-b border-gray-800 fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <span
                        class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                        THE FRAMEWORK
                    </span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="https://github.com/Chandra2004/THE-FRAMEWORK" target="_blank"
                        class="text-gray-400 hover:text-cyan-400 transition-all flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Main Content -->
    <main class="pt-32 pb-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-12">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                User Management
            </h1>
            <button id="openModalButton"
                class="text-white bg-cyan-500 hover:bg-cyan-600 focus:ring-4 focus:outline-none focus:ring-cyan-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center transition-transform hover:scale-105">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add User
            </button>
        </div>

        <div class="mb-8">
            <a href="{{ url('/') }}" class="text-cyan-400 hover:text-cyan-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Homepage
            </a>
        </div>

        @if (!empty($notification))
            <div class="alert mb-8 p-4 rounded-lg border border-{{ $notification['status'] == 'success' ? 'cyan-500/30' : 'red-500/30' }} bg-gradient-to-r from-gray-800/50 to-gray-900/50 backdrop-blur-sm relative overflow-hidden transition-all duration-300 auto-dismiss">
                <!-- Gradient accent bar -->
                <div class="absolute left-0 top-0 h-full w-1.5 bg-gradient-to-b from-{{ $notification['status'] == 'success' ? 'cyan-500' : 'red-500' }} to-{{ $notification['status'] == 'success' ? 'blue-600' : 'pink-600' }}"></div>

                <div class="flex items-start gap-3 pl-4">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        @if ($notification['status'] != 'success')
                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-cyan-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </div>

                    <!-- Content -->
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-{{ $notification['status'] == 'success' ? 'cyan-400' : 'red-400' }} mb-1">
                            {{ ucfirst($notification['status']) }}
                        </h3>
                        <p class="text-sm text-gray-300 leading-tight">
                            {{ $notification['message'] ?? 'An error occurred.' }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($error))
            <div class="alert mb-8 p-4 rounded-lg border border-red-500/30 bg-gradient-to-r from-gray-800/50 to-gray-900/50 backdrop-blur-sm relative overflow-hidden transition-all duration-300 auto-dismiss">
                <!-- Gradient accent bar -->
                <div class="absolute left-0 top-0 h-full w-1.5 bg-gradient-to-b from-red-500 to-pink-600"></div>

                <div class="flex items-start gap-3 pl-4">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-red-400 mb-1">
                            Error
                        </h3>
                        <p class="text-sm text-gray-300 leading-tight">
                            {{ $error }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- User List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if (!empty($userData))
                @foreach ($userData as $userItem)
                    <a href="{{ url('user/information/' . $userItem['id']) }}"
                        class="bg-gray-800/50 p-6 rounded-xl border border-gray-700/50 hover:border-cyan-400/30 transition-all group transform hover:-translate-y-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3
                                    class="text-xl font-semibold text-gray-100 mb-1 group-hover:text-cyan-400 transition-colors">
                                    {{ $userItem['name'] }}
                                </h3>
                                <p class="text-gray-400 text-sm">
                                    Member since {{ $userItem['created_at'] }}
                                </p>
                            </div>
                            <div class="bg-cyan-400/10 p-3 rounded-lg group-hover:bg-cyan-400/20 transition-colors">
                                <svg class="w-6 h-6 text-cyan-400 group-hover:scale-110 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            @else
                <div
                    class="col-span-full bg-gray-800/50 p-8 rounded-2xl border border-gray-700/50 text-center transform hover:scale-[1.01] transition-all">
                    <div class="text-cyan-400 mb-4">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-100 mb-2">No Users Found</h3>
                    <p class="text-gray-400">Create your first user to get started</p>
                </div>
            @endif
        </div>
    </main>

    <!-- Enhanced Add User Modal -->
    <div id="addUserModal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full justify-center items-center backdrop-blur-sm">
        <div class="relative p-4 w-full max-w-md h-full md:h-auto">
            <div class="relative bg-gray-800 rounded-xl border border-gray-700/50 shadow-2xl overflow-hidden">
                <!-- Gradient Header -->
                <div class="bg-gradient-to-r from-cyan-500/30 to-blue-600/30 p-6">
                    <h3 class="text-2xl font-bold text-white">
                        <span class="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                            Create New User
                        </span>
                    </h3>
                    <p class="text-sm text-cyan-100/80 mt-1">Add a new member to your system</p>
                </div>

                <!-- Close Button -->
                <button type="button" id="closeModalButton"
                    class="absolute top-4 right-4 text-gray-400 hover:text-cyan-400 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="py-6 px-6 lg:px-8">
                    <form class="space-y-6" action="{{ url('user') }}" method="POST"
                        enctype="multipart/form-data">
                        <!-- Name Input -->
                        <div class="relative group">
                            @csrf
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-cyan-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input type="text" name="name" id="name"
                                class="bg-gray-700/50 border border-gray-600/50 text-white text-sm rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 block w-full pl-10 p-3.5 hover:border-cyan-400/50 transition-all"
                                placeholder="Full Name" required>
                        </div>

                        <!-- Email Input -->
                        <div class="relative group">
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-cyan-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="email" name="email" id="email"
                                class="bg-gray-700/50 border border-gray-600/50 text-white text-sm rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 block w-full pl-10 p-3.5 hover:border-cyan-400/50 transition-all"
                                placeholder="name@company.com" required>
                        </div>

                        <!-- File Upload with Preview -->
                        <div class="relative group">
                            <label
                                class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-600/50 rounded-lg cursor-pointer bg-gray-700/50 hover:border-cyan-400/50 hover:bg-gray-700/70 transition-all">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center">
                                    <svg class="w-8 h-8 mb-3 text-cyan-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-sm text-gray-400">
                                        <span class="font-semibold text-cyan-400">Click to upload</span> or drag and
                                        drop
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG (MAX. 2MB)</p>
                                </div>
                                <input type="file" name="profile_picture" id="profile_picture" class="hidden"
                                    accept="image/*">
                            </label>
                            <!-- Image Preview -->
                            <div id="imagePreview" class="mt-4 hidden">
                                <img id="previewImage"
                                    class="w-full h-32 object-cover rounded-lg border border-cyan-400/50"
                                    src="#" alt="Image Preview">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                            class="w-full py-3.5 px-6 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all transform hover:scale-[1.02] shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/30">
                            Create User
                            <svg class="w-4 h-4 ml-2 inline-block" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-gray-800 mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-gray-500">
                <p class="text-sm">
                    © 2024 THE FRAMEWORK •
                    <a href="https://www.instagram.com/chandratriantomo.2077/" target="_blank"
                        class="hover:text-cyan-400 transition-all">
                        Crafted by Chandra Tri A
                    </a>
                </p>
            </div>
        </div>
    </footer>

    <!-- Modal Script -->
    <script nonce="<?= $nonce ?>">
        const openModalButton = document.getElementById('openModalButton');
        const closeModalButton = document.getElementById('closeModalButton');
        const addUserModal = document.getElementById('addUserModal');

        // Modal Handling
        function toggleModal(show) {
            addUserModal.classList.toggle('hidden', !show);
            document.body.style.overflow = show ? 'hidden' : 'auto';
        }

        openModalButton.addEventListener('click', () => toggleModal(true));
        closeModalButton.addEventListener('click', () => toggleModal(false));

        window.addEventListener('click', (event) => {
            if (event.target === addUserModal) toggleModal(false)
        });

        // File Upload Preview
        const fileInput = document.getElementById('profile_picture');
        fileInput.addEventListener('change', function(e) {
            const fileName = document.getElementById('file-name');
            if (this.files[0]) {
                if (!fileName) {
                    const fileNameDisplay = document.createElement('p');
                    fileNameDisplay.id = 'file-name';
                    fileNameDisplay.className = 'text-sm text-cyan-400 mt-2';
                    this.parentNode.appendChild(fileNameDisplay);
                }
                document.getElementById('file-name').textContent = this.files[0].name;
            }
        });
    </script>
</body>

</html>
