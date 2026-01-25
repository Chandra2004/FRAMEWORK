@extends('template.layout')

@section('meta_title')
    Member Directory | THE-FRAMEWORK Management Console
@endsection

@section('meta_description')
    Manage your team members and users with an elegant, lightning-fast interface. Powered by THE-FRAMEWORK.
@endsection

@section('meta_keywords')
    user management system, php member directory, mvc user crud, secure member portal
@endsection

@section('main-content')
    <!-- Main Content Wrapper with Premium Spacing -->
    <main class="min-h-screen pt-32 pb-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto flex flex-col relative z-0">

        <!-- HEADER SECTION -->
        <div class="flex flex-col md:flex-row justify-between items-end md:items-center mb-12 gap-6 animate-fade-in">
            <div>
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-white mb-3">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 to-blue-600">
                        {{ __('messages.user_management') }}
                    </span>
                </h1>
                <p class="text-slate-400 text-lg max-w-2xl">
                    {{ __('messages.user_management_desc') }}
                </p>
            </div>

            <!-- Add User Button (Premium Glow Effect) -->
            <button onclick="openModal()"
                class="group relative inline-flex items-center justify-center px-8 py-3.5 text-base font-bold text-white transition-all duration-300 bg-cyan-600 rounded-xl hover:bg-cyan-500 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-cyan-500/20">
                <span class="relative flex items-center gap-2">
                    <i data-lucide="user-plus" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
                    {{ __('messages.add_new_member') }}
                </span>
            </button>
        </div>

        <!-- USERS GRID -->
        <div id="userGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($users as $user)
                <!-- User Card Item -->
                <article>
                    <a href="/users/information/{{ $user['uid'] }}"
                        class="group relative block glass-card rounded-2xl p-6 hover:border-cyan-500/50 transition-all duration-500 transform hover:-translate-y-2 hover:shadow-2xl hover:shadow-cyan-500/10 overflow-hidden">

                        <div class="flex items-center gap-5">
                            <!-- Avatar / Initials -->
                            <div class="relative shrink-0">
                                @if (!empty($user['profile_picture']))
                                    <img src="{{ url('/file/user-pictures/' . $user['profile_picture']) }}"
                                        alt="{{ $user['name'] }}"
                                        class="w-16 h-16 rounded-2xl object-cover border border-slate-700 group-hover:border-cyan-400 transition-colors shadow-xl">
                                @else
                                    <div
                                        class="w-16 h-16 rounded-2xl bg-slate-800 border border-slate-700 group-hover:border-cyan-400 flex items-center justify-center text-xl font-bold text-slate-400 group-hover:text-cyan-400 transition-all shadow-xl">
                                        {{ strtoupper(substr($user['name'], 0, 1)) }}
                                    </div>
                                @endif
                                <!-- Status Indicator (Active) -->
                                <div
                                    class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-slate-900 rounded-full shadow-lg pulse-emerald">
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="relative z-10 min-w-0 flex-1">
                                <h3
                                    class="text-xl font-bold text-white truncate group-hover:text-cyan-400 transition-colors mb-0.5">
                                    {{ $user['name'] }}
                                </h3>
                                <p class="text-sm text-slate-400 truncate mb-2">{{ $user['email'] ?? 'No Email' }}</p>
                                <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                    <span>{{ __('messages.joined') }}
                                        {{ date('M d, Y', strtotime($user['created_at'])) }}</span>
                                </div>
                            </div>

                            <!-- Arrow Icon -->
                            <div
                                class="text-slate-600 group-hover:text-cyan-400 transition-all transform group-hover:translate-x-1">
                                <i data-lucide="chevron-right" class="w-6 h-6"></i>
                            </div>
                        </div>
                    </a>
                </article>
            @empty
                <!-- Empty State -->
                <div id="emptyState"
                    class="col-span-full py-24 flex flex-col items-center justify-center text-center border-2 border-dashed border-slate-800 rounded-3xl bg-slate-900/50">
                    <div class="w-20 h-20 bg-slate-800 rounded-2xl mb-6 flex items-center justify-center">
                        <i data-lucide="users-round" class="w-10 h-10 text-slate-500"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">{{ __('messages.no_members') }}</h3>
                    <p class="text-slate-400 mb-8 max-w-sm">{{ __('messages.no_members_desc') }}</p>
                    <button onclick="openModal()"
                        class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold rounded-xl transition-all border border-slate-700">
                        {{ __('messages.add_first_member') }}
                    </button>
                </div>
            @endforelse
        </div>
    </main>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div id="toast-container" class="fixed bottom-8 right-8 z-[60] flex flex-col gap-3 pointer-events-none"></div>

    <!-- MODAL -->
    <div id="customModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div id="modalBackdrop"
            class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity duration-300 opacity-0"
            aria-hidden="true"></div>

        <!-- Modal Panel -->
        <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div id="modalPanel"
                    class="relative transform overflow-hidden rounded-3xl bg-slate-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-800 opacity-0 translate-y-8 scale-95 duration-300">

                    <!-- Modal Header -->
                    <div class="px-8 pt-8 pb-6 flex items-start justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-white">{{ __('messages.create_user') }}</h3>
                            <p class="text-slate-400 mt-1">{{ __('messages.create_user_desc') }}</p>
                        </div>
                        <button type="button" onclick="closeModal()"
                            class="text-slate-400 hover:text-white transition-colors bg-slate-800/50 hover:bg-slate-800 rounded-xl p-2.5">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form id="createUserForm" action="/users/create" method="POST" class="px-8 pb-8 space-y-6"
                        enctype="multipart/form-data">
                        @csrf

                        <!-- Name Field -->
                        <div class="space-y-2">
                            <label for="name"
                                class="block text-sm font-semibold text-slate-300">{{ __('messages.full_name') }}</label>
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500 group-focus-within:text-cyan-400 transition-colors">
                                    <i data-lucide="user" class="w-5 h-5"></i>
                                </div>
                                <input type="text" name="name" id="name" required
                                    class="block w-full rounded-xl border-slate-700 bg-slate-950 pl-11 py-3.5 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all outline-none"
                                    placeholder="e.g. John Doe">
                            </div>
                        </div>

                        <!-- Email Field -->
                        <div class="space-y-2">
                            <label for="email"
                                class="block text-sm font-semibold text-slate-300">{{ __('messages.email_address') }}</label>
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500 group-focus-within:text-cyan-400 transition-colors">
                                    <i data-lucide="mail" class="w-5 h-5"></i>
                                </div>
                                <input type="email" name="email" id="email" required
                                    class="block w-full rounded-xl border-slate-700 bg-slate-950 pl-11 py-3.5 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all outline-none"
                                    placeholder="john@example.com">
                            </div>
                        </div>

                        <!-- File Upload Area -->
                        <div class="space-y-2">
                            <label
                                class="block text-sm font-semibold text-slate-300">{{ __('messages.profile_picture') }}</label>
                            <div class="group relative flex justify-center rounded-2xl border-2 border-dashed border-slate-700 px-6 py-8 hover:border-cyan-500 hover:bg-cyan-500/5 transition-all cursor-pointer overflow-hidden"
                                onclick="document.getElementById('profile_picture').click()">

                                <div class="space-y-2 text-center relative z-10">
                                    <!-- Preview Image -->
                                    <div id="previewContainer" class="hidden mb-4">
                                        <img id="previewImage" src="#" alt="Preview"
                                            class="mx-auto h-24 w-24 rounded-2xl object-cover border-2 border-cyan-500 ring-8 ring-cyan-500/10">
                                        <p id="fileName" class="text-sm text-cyan-400 mt-3 font-bold"></p>
                                    </div>

                                    <!-- Default State -->
                                    <div id="uploadIcon" class="flex flex-col items-center">
                                        <div
                                            class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                            <i data-lucide="image-plus"
                                                class="w-6 h-6 text-slate-400 group-hover:text-cyan-400"></i>
                                        </div>
                                        <div class="text-sm font-medium text-slate-400">
                                            <span
                                                class="text-cyan-400 font-bold group-hover:text-cyan-300">{{ __('messages.upload_file') }}</span>
                                            <span class="mx-1">{{ __('messages.drag_drop') }}</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">SVG, PNG, JPG (Max. 2MB)</p>
                                    </div>
                                    <input id="profile_picture" name="profile_picture" type="file" class="hidden"
                                        accept="image/*">
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-4 pt-4">
                            <button type="button" onclick="closeModal()"
                                class="flex-1 px-6 py-3.5 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700 transition-all">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" id="submitBtn"
                                class="flex-1 px-6 py-3.5 bg-cyan-600 text-white font-bold rounded-xl hover:bg-cyan-500 transition-all flex items-center justify-center gap-2 group">
                                <span id="btnText">{{ __('messages.save_member') }}</span>
                                <i data-lucide="arrow-right"
                                    class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                                <div id="spinner"
                                    class="hidden w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin">
                                </div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- 1. MODAL SYSTEM ---
        const modal = document.getElementById('customModal');
        const modalBackdrop = document.getElementById('modalBackdrop');
        const modalPanel = document.getElementById('modalPanel');

        function openModal() {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalBackdrop.classList.remove('opacity-0');
                modalPanel.classList.remove('opacity-0', 'translate-y-8', 'scale-95');
                modalPanel.classList.add('opacity-100', 'translate-y-0', 'scale-100');
            }, 50);
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modalBackdrop.classList.add('opacity-0');
            modalPanel.classList.add('opacity-0', 'translate-y-8', 'scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
                resetForm();
            }, 300);
        }

        function resetForm() {
            document.getElementById('createUserForm').reset();
            document.getElementById('previewContainer').classList.add('hidden');
            document.getElementById('uploadIcon').classList.remove('hidden');
        }

        // --- 2. FILE PREVIEW ---
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                    document.getElementById('fileName').textContent = file.name;
                    document.getElementById('previewContainer').classList.remove('hidden');
                    document.getElementById('uploadIcon').classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        });

        // --- 3. FORM SUBMIT ---
        document.getElementById('createUserForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            document.getElementById('spinner').classList.remove('hidden');
            document.getElementById('btnText').textContent = 'Processing...';
        });

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();
        });
    </script>
@endsection
