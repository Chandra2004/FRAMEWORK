@extends('template.layout')
@section('main-content')
    <main>
        <!-- Hero Section -->
        <header class="pt-32 pb-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" role="banner">
            <div class="text-center">
                <h1 class="text-5xl font-bold text-gray-100 mb-6 leading-tight">
                    {{ __('messages.hero_title') }}
                    <span class="block mt-4 bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                        {{ __('messages.hero_subtitle') }}
                    </span>
                </h1>
                <p class="text-xl text-gray-400 mb-8 max-w-3xl mx-auto">
                    {{ __('messages.hero_description') }}
                </p>

                <!-- CTA Button -->
                <div class="flex justify-center space-x-4 mb-16">
                    <a href="{{ url('users') }}" rel="noopener noreferrer"
                        class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white px-8 py-4 rounded-lg font-medium 
                            hover:from-cyan-600 hover:to-blue-700 transition-all shadow-2xl hover:shadow-cyan-500/20
                            flex items-center gap-2"
                        aria-label="Get Started with THE FRAMEWORK">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        {{ __('messages.start_building') }}
                    </a>
                </div>
            </div>
        </header>

        <!-- Features Section -->
        <section class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" aria-labelledby="features-heading">
            <h2 id="features-heading" class="sr-only">Key Features</h2>

            <div class="grid md:grid-cols-3 gap-8 mb-24">
                <!-- Feature 1 -->
                <article
                    class="bg-gray-800/50 p-8 rounded-2xl hover:bg-gray-800/80 transition-all border border-gray-700/50 hover:border-cyan-400/30 text-center">
                    <div class="w-14 h-14 bg-cyan-400/10 rounded-xl mb-6 mx-auto flex items-center justify-center">
                        <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-100 mb-3">{{ __('messages.features_performance') }}</h3>
                    <p class="text-gray-400 px-4">{{ __('messages.features_performance_desc') }}</p>
                </article>

                <!-- Feature 2 -->
                <article
                    class="bg-gray-800/50 p-8 rounded-2xl hover:bg-gray-800/80 transition-all border border-gray-700/50 hover:border-cyan-400/30 text-center">
                    <div class="w-14 h-14 bg-cyan-400/10 rounded-xl mb-6 mx-auto flex items-center justify-center">
                        <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-100 mb-3">{{ __('messages.features_secure') }}</h3>
                    <p class="text-gray-400 px-4">{{ __('messages.features_secure_desc') }}</p>
                </article>

                <!-- Feature 3 -->
                <article
                    class="bg-gray-800/50 p-8 rounded-2xl hover:bg-gray-800/80 transition-all border border-gray-700/50 hover:border-cyan-400/30 text-center">
                    <div class="w-14 h-14 bg-cyan-400/10 rounded-xl mb-6 mx-auto flex items-center justify-center">
                        <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-100 mb-3">{{ __('messages.features_api') }}</h3>
                    <p class="text-gray-400 px-4">{{ __('messages.features_api_desc') }}</p>
                </article>
            </div>
        </section>

        <!-- Database Status -->
        <section class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto text-center" aria-labelledby="db-status">
            <h2 id="db-status" class="sr-only">Database Connection Status</h2>
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-700/50 max-w-md mx-auto">
                <div class="flex items-center justify-center space-x-3">
                    @if ($status == 'success')
                        <div class="w-9 h-9 bg-cyan-400/10 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="text-center">
                            <p class="text-cyan-400 font-medium">{{ __('messages.db_connected') }}</p>
                            <p class="text-gray-400 text-sm">{{ __('messages.db_ready') }}</p>
                        </div>
                    @else
                        <div class="w-9 h-9 bg-red-400/10 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div class="text-center">
                            <p class="text-red-400 font-medium">{{ __('messages.db_error') }}</p>
                            <p class="text-gray-400 text-sm">{{ __('messages.db_check') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>
@endsection
