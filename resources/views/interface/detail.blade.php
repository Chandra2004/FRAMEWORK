@extends('template.layout')

@section('meta_title', 'User Profile: ' . $user['name'] . ' | THE-FRAMEWORK')
@section('meta_description', 'View and manage profile details for ' . $user['name'] . '. Account management powered by
    THE-FRAMEWORK.')

@section('main-content')
    <main class="pt-32 pb-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="max-w-4xl mx-auto">
            <!-- Back Button -->
            <div class="mb-10 flex items-center justify-between animate-fade-in">
                <a href="/users"
                    class="group flex items-center gap-2 text-slate-400 hover:text-cyan-400 font-bold transition-all transform hover:-translate-x-1">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    {{ __('messages.back_to_users') }}
                </a>

                <div class="flex items-center gap-3">
                    <span
                        class="px-3 py-1 bg-slate-800/50 border border-slate-700 rounded-lg text-xs font-mono text-slate-400">
                        UID: {{ $user['uid'] }}
                    </span>
                </div>
            </div>

            <!-- Main Profile Card -->
            <div class="glass-card rounded-3xl overflow-hidden border-slate-800/50 shadow-2xl animate-fade-in">
                <div class="md:flex">
                    <!-- Sidebar: Profile Overview -->
                    <div
                        class="md:w-1/3 bg-slate-800/30 p-8 border-b md:border-b-0 md:border-r border-slate-700/50 text-center">
                        <div class="relative inline-block mb-6">
                            <?php
                            $profileUrl = $user['profile_picture'] ? url('/file/user-pictures/' . $user['profile_picture']) : url('/file/dummy/dummy.webp');
                            ?>
                            <div
                                class="w-32 h-32 rounded-3xl overflow-hidden border-2 border-slate-700 group-hover:border-cyan-400 transition-all shadow-2xl mx-auto ring-8 ring-slate-800/50">
                                <img src="<?php echo $profileUrl; ?>" alt="{{ $user['name'] }}" class="w-full h-full object-cover">
                            </div>
                            <div
                                class="absolute -bottom-2 -right-2 w-8 h-8 bg-emerald-500 rounded-xl border-4 border-slate-900 flex items-center justify-center shadow-lg">
                                <i data-lucide="check" class="w-4 h-4 text-white"></i>
                            </div>
                        </div>

                        <h1 class="text-2xl font-bold text-white mb-1 truncate">{{ $user['name'] }}</h1>
                        <p class="text-slate-400 text-sm mb-6 truncate">{{ $user['email'] }}</p>

                        <div class="space-y-3">
                            <div class="p-3 bg-slate-900/50 rounded-xl border border-slate-800 text-left">
                                <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-1">Created At</p>
                                <p class="text-xs text-slate-300">{{ date('d M Y, H:i', strtotime($user['created_at'])) }}
                                </p>
                            </div>
                            <div class="p-3 bg-slate-900/50 rounded-xl border border-slate-800 text-left">
                                <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-1">Security Score
                                </p>
                                <div class="flex items-center gap-2">
                                    <div class="flex-grow h-1 bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-cyan-500 w-[85%]"></div>
                                    </div>
                                    <span class="text-[10px] text-cyan-400 font-bold">85%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Danger Zone Button -->
                        <div class="mt-8 pt-8 border-t border-slate-700/50">
                            <form id="deleteUserForm" action="/users/delete/{{ $user['uid'] }}" method="POST">
                                @csrf
                                <button type="submit" id="deleteBtn"
                                    class="group flex items-center justify-center gap-2 w-full py-3 bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white border border-rose-500/20 rounded-xl font-bold transition-all duration-300">
                                    <i data-lucide="trash-2" class="w-4 h-4 group-hover:animate-bounce"></i>
                                    <span id="deleteBtnText">{{ __('messages.delete_user') }}</span>
                                    <div id="deleteSpinner"
                                        class="hidden w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin">
                                    </div>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Content: Edit Form -->
                    <div class="md:w-2/3 p-8 md:p-12">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 bg-cyan-500/10 rounded-xl flex items-center justify-center text-cyan-400">
                                <i data-lucide="user-cog" class="w-6 h-6"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-white">{{ __('messages.edit_user') }}</h2>
                        </div>

                        <form id="updateUserForm" action="/users/update/{{ $user['uid'] }}" method="POST"
                            class="space-y-8" enctype="multipart/form-data">
                            @csrf

                            <!-- Name & Email Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="name"
                                        class="block text-sm font-semibold text-slate-400">{{ __('messages.full_name') }}</label>
                                    <div class="relative">
                                        <i data-lucide="user"
                                            class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-600"></i>
                                        <input type="text" name="name" id="name" value="{{ $user['name'] }}"
                                            required
                                            class="w-full bg-slate-950 border border-slate-800 rounded-2xl pl-12 pr-4 py-4 text-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all outline-none">
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label for="email"
                                        class="block text-sm font-semibold text-slate-400">{{ __('messages.email_address') }}</label>
                                    <div class="relative">
                                        <i data-lucide="mail"
                                            class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-600"></i>
                                        <input type="email" name="email" id="email" value="{{ $user['email'] }}"
                                            required
                                            class="w-full bg-slate-950 border border-slate-800 rounded-2xl pl-12 pr-4 py-4 text-white focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all outline-none">
                                    </div>
                                </div>
                            </div>

                            <!-- Profile Picture Upload -->
                            <div class="space-y-3">
                                <label
                                    class="block text-sm font-semibold text-slate-400">{{ __('messages.profile_picture') }}</label>
                                <div class="group relative flex justify-center items-center py-10 border-2 border-dashed border-slate-800 rounded-3xl hover:border-cyan-500 hover:bg-cyan-500/5 transition-all cursor-pointer overflow-hidden"
                                    onclick="document.getElementById('profile_picture').click()">

                                    <div class="text-center space-y-2">
                                        <i data-lucide="upload-cloud"
                                            class="w-10 h-10 text-slate-600 mx-auto group-hover:text-cyan-400 transition-colors"></i>
                                        <p class="text-sm font-bold text-slate-400">
                                            <span class="text-cyan-400">{{ __('messages.upload_file') }}</span>
                                            or drag and drop
                                        </p>
                                        <p class="text-xs text-slate-600">Optimal size 512x512px. PNG, WEBP, JPG.</p>
                                    </div>
                                    <input type="file" name="profile_picture" id="profile_picture" class="hidden"
                                        accept="image/*">
                                </div>

                                <!-- Image Preview -->
                                <div id="imagePreview" class="hidden pt-4 animate-fade-in">
                                    <div
                                        class="flex items-center gap-4 p-4 bg-slate-950 rounded-2xl border border-cyan-500/30">
                                        <img id="previewImage" src="#" alt="Preview"
                                            class="w-16 h-16 rounded-xl object-cover">
                                        <div class="flex-grow">
                                            <p id="fileName" class="text-sm text-white font-bold truncate max-w-[200px]">
                                            </p>
                                            <p class="text-xs text-cyan-400">Ready to upload</p>
                                        </div>
                                        <button type="button" onclick="resetFile()"
                                            class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 pt-2">
                                    <input type="checkbox" name="delete_profile_picture" id="delete_profile_picture"
                                        class="w-5 h-5 rounded-lg border-slate-800 bg-slate-950 text-cyan-500 focus:ring-cyan-500 transition-all">
                                    <label for="delete_profile_picture"
                                        class="text-sm font-bold text-slate-400 cursor-pointer hover:text-slate-300">
                                        {{ __('messages.delete_profile_picture') }}
                                    </label>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="pt-6 flex flex-col md:flex-row gap-4">
                                <button type="submit" id="updateBtn"
                                    class="flex-1 py-4 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-2xl transition-all flex items-center justify-center gap-2 group shadow-xl shadow-cyan-500/10 active:scale-[0.98]">
                                    <span id="updateBtnText">{{ __('messages.update_user') }}</span>
                                    <i data-lucide="save" class="w-5 h-5 group-hover:scale-110"></i>
                                    <div id="updateSpinner"
                                        class="hidden w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin">
                                    </div>
                                </button>
                                <a href="/users"
                                    class="flex-1 py-4 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-2xl transition-all text-center">
                                    Cancel Changes
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Footer -->
            <div class="mt-8 glass-card p-6 rounded-3xl flex items-center gap-5 border-slate-800/30">
                <div class="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shield-alert" class="w-6 h-6 text-amber-500"></i>
                </div>
                <div>
                    <h3 class="text-white font-bold mb-0.5">{{ __('messages.security_note') }}</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">{{ __('messages.security_note_desc') }}</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();

            // --- FILE PREVIEW ---
            const picInput = document.getElementById('profile_picture');
            if (picInput) {
                picInput.addEventListener('change', function(e) {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('previewImage').src = e.target.result;
                            document.getElementById('fileName').textContent = file.name;
                            document.getElementById('imagePreview').classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // --- FORM HANDLERS ---
            const updateForm = document.getElementById('updateUserForm');
            if (updateForm) {
                updateForm.addEventListener('submit', function() {
                    const btn = document.getElementById('updateBtn');
                    btn.disabled = true;
                    document.getElementById('updateSpinner').classList.remove('hidden');
                    document.getElementById('updateBtnText').textContent = 'Processing...';
                });
            }

            // --- DELETE SYSTEM ---
            const deleteForm = document.getElementById('deleteUserForm');
            const deleteBtn = document.getElementById('deleteBtn');
            let confirmStep = false;

            if (deleteForm && deleteBtn) {
                deleteForm.addEventListener('submit', function(e) {
                    if (!confirmStep) {
                        e.preventDefault();
                        confirmStep = true;
                        deleteBtn.querySelector('span').textContent = 'Are you really sure?';
                        deleteBtn.classList.remove('bg-rose-500/10');
                        deleteBtn.classList.add('bg-rose-600', 'animate-pulse');
                        deleteBtn.classList.add('text-white');

                        setTimeout(() => {
                            if (confirmStep) {
                                confirmStep = false;
                                deleteBtn.querySelector('span').textContent =
                                    '{{ __('messages.delete_user') }}';
                                deleteBtn.classList.add('bg-rose-500/10');
                                deleteBtn.classList.remove('bg-rose-600', 'animate-pulse');
                                deleteBtn.classList.remove('text-white');
                            }
                        }, 4000);
                        return;
                    }

                    deleteBtn.disabled = true;
                    document.getElementById('deleteSpinner').classList.remove('hidden');
                    deleteBtn.querySelector('span').textContent = 'Removing...';
                });
            }
        });

        function resetFile() {
            document.getElementById('profile_picture').value = '';
            document.getElementById('imagePreview').classList.add('hidden');
        }
    </script>
@endsection
