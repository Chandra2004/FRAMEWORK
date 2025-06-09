<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🐦</text></svg>">
    
    <!-- Primary Meta Tags -->
    <title>User Details - THE FRAMEWORK</title>
    <meta name="description" content="User detail information page for THE FRAMEWORK framework">
    <meta name="keywords" content="User Details, PHP Framework, MVC, Database">
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
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-gray-900/20 backdrop-blur-lg border-b border-gray-800 fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <span class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                        THE FRAMEWORK
                    </span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="https://github.com/Chandra2004/THE-FRAMEWORK" 
                       target="_blank" 
                       class="text-gray-400 hover:text-cyan-400 transition-all flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-32 pb-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="max-w-3xl mx-auto">
            <div class="mb-8 flex items-center justify-between">
                <a href="{{ url('user') }}" class="text-cyan-400 hover:text-cyan-300 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Users
                </a>

                <button id="openModalButton" class="text-white bg-cyan-500 hover:bg-cyan-600 focus:ring-4 focus:outline-none focus:ring-cyan-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center transition-transform hover:scale-105">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add User
                </button>
            </div>

            <!-- User Card -->
            <div class="bg-gray-800/50 p-8 rounded-2xl border border-gray-700/50">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                        User Details
                    </h1>
                    <span class="bg-cyan-400/10 text-cyan-400 px-4 py-2 rounded-full text-sm">
                        ID: {{ $user['id'] }}
                    </span>
                </div>

                <form action="{{ url('user/' . $user['id'] . '/update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent mb-6">
                        Edit User
                    </h3>
                    @csrf
                    <!-- Name Input -->
                    <div class="relative group">
                        <label for="name" class="block text-sm font-medium text-gray-400 mb-2">Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-cyan-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
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
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-cyan-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input type="email" name="email" id="email" value="{{ $user['email'] }}" 
                                   class="bg-gray-700/50 border border-gray-600/50 text-white text-sm rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 block w-full pl-10 p-3.5 hover:border-cyan-400/50 transition-all"
                                   placeholder="name@company.com" required>
                        </div>
                    </div>
                
                    <!-- Profile Picture Upload -->
                    <div class="relative group">
                        <label for="profile_picture" class="block text-sm font-medium text-gray-400 mb-2">Profile Picture</label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-600/50 rounded-lg cursor-pointer bg-gray-700/50 hover:border-cyan-400/50 hover:bg-gray-700/70 transition-all">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center">
                                    <svg class="w-8 h-8 mb-3 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-sm text-gray-400">
                                        <span class="font-semibold text-cyan-400">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG (MAX. 2MB)</p>
                                </div>
                                <input type="file" name="profile_picture" id="profile_picture" class="hidden" accept="image/jpeg, image/png">
                            </label>
                        </div>
                        <!-- Image Preview -->
                        <div id="imagePreview" class="mt-4 hidden">
                            <img id="previewImage" class="w-32 h-32 object-cover rounded-lg border border-cyan-400/50" src="#" alt="Image Preview">
                        </div>
                    </div>

                    <!-- Delete Profile Picture Checkbox -->
                    <div class="flex items-center mt-4">
                        <input type="checkbox" name="delete_profile_picture" id="delete_profile_picture" class="w-5 h-5 text-cyan-500 bg-gray-700 border-gray-600 rounded focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                        <label for="delete_profile_picture" class="ml-2 text-gray-400 text-sm">Delete profile picture</label>
                    </div>
                
                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-3.5 px-6 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all transform hover:scale-[1.02] shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/30">
                        Update User
                        <svg class="w-4 h-4 ml-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </form>
                
                <!-- Script for Image Preview -->
                <script>
                    const profilePictureInput = document.getElementById('profile_picture');
                    const imagePreview = document.getElementById('imagePreview');
                    const previewImage = document.getElementById('previewImage');
                
                    profilePictureInput.addEventListener('change', function() {
                        const file = this.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                previewImage.src = e.target.result;
                                imagePreview.classList.remove('hidden');
                            }
                            reader.readAsDataURL(file);
                        } else {
                            imagePreview.classList.add('hidden');
                        }
                    });
                </script>
                
                <!-- User Info -->
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Aksi</p>
                            <a href="{{ url('user/' . $user['id'] . '/delete') }}" onclick="return confirm('Are you sure you want to delete this user?');" class="text-sm font-medium text-red-800 group-hover:text-cyan-400 transition-colors">hapus {{ $user['name'] }}</a>
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
                                        {{ $user['created_at'] }}
                                    </p>
                                </li>
                                <li>
                                    <p class="text-gray-400 text-sm mb-1">Account Update</p>
                                    <p class="text-gray-100 font-medium">
                                        {{ $user['updated_at'] }}
                                    </p>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Profile Picture</p>
                            <img src="{{ url('file.php?file=' . ('user-pictures/'. $user['profile_picture'] ?? 'dummy/dummy.jpg')) }}" alt="{{ $user['name'] }}" loading="lazy">
                        </div>
                    </div>

                    <!-- Security Badge -->
                    <div class="mt-8 p-4 bg-gray-900/50 rounded-lg border border-cyan-400/20 flex items-center gap-4">
                        <div class="bg-cyan-400/10 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
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

    <!-- Enhanced Add User Modal -->
    <div id="addUserModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 h-modal md:h-full justify-center items-center backdrop-blur-sm">
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
                <button type="button" id="closeModalButton" class="absolute top-4 right-4 text-gray-400 hover:text-cyan-400 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="py-6 px-6 lg:px-8">
                    <form class="space-y-6" action="{{ url('user') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <!-- Name Input -->
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-cyan-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input type="text" name="name" id="name" 
                                   class="bg-gray-700/50 border border-gray-600/50 text-white text-sm rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 block w-full pl-10 p-3.5 hover:border-cyan-400/50 transition-all"
                                   placeholder="Full Name" required>
                        </div>

                        <!-- Email Input -->
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-cyan-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input type="email" name="email" id="email" 
                                   class="bg-gray-700/50 border border-gray-600/50 text-white text-sm rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 block w-full pl-10 p-3.5 hover:border-cyan-400/50 transition-all"
                                   placeholder="name@company.com" required>
                        </div>

                        <!-- File Upload with Preview -->
                        <div class="relative group">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-600/50 rounded-lg cursor-pointer bg-gray-700/50 hover:border-cyan-400/50 hover:bg-gray-700/70 transition-all">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center">
                                    <svg class="w-8 h-8 mb-3 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-sm text-gray-400">
                                        <span class="font-semibold text-cyan-400">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG (MAX. 2MB)</p>
                                </div>
                                <input type="file" name="profile_picture" id="profile_picture" class="hidden" accept="image/*" required>
                            </label>
                            <!-- Image Preview -->
                            <div id="imagePreview" class="mt-4 hidden">
                                <img id="previewImage" class="w-full h-32 object-cover rounded-lg border border-cyan-400/50" src="#" alt="Image Preview">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full py-3.5 px-6 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all transform hover:scale-[1.02] shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/30">
                            Create User
                            <svg class="w-4 h-4 ml-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
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
                    <a href="https://www.instagram.com/chandratriantomo.2077/" 
                       target="_blank" 
                       class="hover:text-cyan-400 transition-all">
                        Crafted by Chandra Tri A
                    </a>
                </p>
            </div>
        </div>
    </footer>

    <!-- Modal Script -->
    <script>
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
            if(this.files[0]) {
                if(!fileName) {
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
