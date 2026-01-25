@extends('template.layout')

@section('meta_title', 'THE-FRAMEWORK | Enterprise PHP Framework for Modern Developers')
@section('meta_description', 'Building lightning-fast web applications with THE-FRAMEWORK. Featuring native database
    migrations, built-in REST API support, and a premium developer experience.')
@section('meta_keywords', 'php framework tutorial, lightweight php framework, best php framework 2026, fast php mvc,
    chandra tri antomo')

@section('extra_head')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "SoftwareApplication",
  "name": "THE-FRAMEWORK",
  "operatingSystem": "Linux, Windows, macOS",
  "applicationCategory": "DeveloperApplication",
  "description": "High-performance PHP framework with Laravel-like features and minimalist architecture.",
  "author": {
    "@@type": "Person",
    "name": "Chandra Tri Antomo"
  },
  "offers": {
    "@@type": "Offer",
    "price": "0",
    "priceCurrency": "USD"
  }
}
</script>
@endsection

@section('main-content')
    <main class="relative overflow-hidden">
        <!-- Background Decorative Elements -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full -z-10 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-cyan-500/10 blur-[120px] rounded-full"></div>
            <div class="absolute bottom-[10%] right-[-10%] w-[30%] h-[30%] bg-blue-600/10 blur-[120px] rounded-full"></div>
        </div>

        <!-- Hero Section -->
        <header class="pt-40 pb-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto text-center" role="banner">
            <div
                class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-xs font-semibold mb-8 animate-fade-in">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
                </span>
                NEW VERSION 5.0.0 RELEASED
            </div>

            <h1 class="text-6xl md:text-7xl font-bold text-white mb-8 tracking-tight leading-[1.1]">
                {{ __('messages.hero_title') }}
                <span
                    class="block mt-2 bg-gradient-to-r from-cyan-400 via-blue-500 to-indigo-600 bg-clip-text text-transparent">
                    {{ __('messages.hero_subtitle') }}
                </span>
            </h1>

            <p class="text-xl text-slate-400 mb-12 max-w-2xl mx-auto leading-relaxed">
                {{ __('messages.hero_description') }}
            </p>

            <div class="flex flex-col sm:flex-row justify-center items-center gap-4 mb-20">
                <a href="{{ url('users') }}"
                    class="group relative px-8 py-4 bg-cyan-500 text-slate-950 rounded-xl font-bold transition-all hover:bg-cyan-400 hover:scale-105 active:scale-95 flex items-center gap-2 overflow-hidden shadow-[0_0_20px_rgba(6,182,212,0.3)]">
                    <i data-lucide="zap" class="w-5 h-5"></i>
                    {{ __('messages.start_building') }}
                    <div
                        class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-500">
                    </div>
                </a>
                <a href="https://github.com/Chandra2004/FRAMEWORK" target="_blank"
                    class="px-8 py-4 bg-slate-800/50 text-white rounded-xl font-bold border border-slate-700 hover:bg-slate-700 transition-all flex items-center gap-2">
                    <i data-lucide="github" class="w-5 h-5"></i>
                    View Source
                </a>
            </div>

            <!-- Dashboard Preview Mockup (Aesthetic) -->
            <div class="max-w-5xl mx-auto bg-slate-900 rounded-2xl border border-slate-800 shadow-2xl p-2 relative">
                <div
                    class="bg-slate-950 rounded-xl overflow-hidden border border-slate-800 aspect-video flex items-center justify-center relative">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent z-10"></div>
                    <code class="text-cyan-400 font-mono text-lg animate-pulse">git clone https://github.com/Chandra2004/FRAMEWORK.git</code>
                </div>
            </div>
        </header>

        <!-- Features Section -->
        <section class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" aria-labelledby="features-heading">
            <div class="text-center mb-16">
                <h2 id="features-heading" class="text-3xl md:text-4xl font-bold text-white mb-4">Powerful Features</h2>
                <p class="text-slate-400">Everything you need to build fast, secure web applications.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <article class="glass-card p-8 rounded-3xl group hover:border-cyan-500/30 transition-all duration-500">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-cyan-500/20 to-blue-600/20 rounded-2xl mb-8 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i data-lucide="gauge" class="w-8 h-8 text-cyan-400 text-glow"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">{{ __('messages.features_performance') }}</h3>
                    <p class="text-slate-400 leading-relaxed">{{ __('messages.features_performance_desc') }}</p>
                </article>

                <!-- Feature 2 -->
                <article class="glass-card p-8 rounded-3xl group hover:border-blue-500/30 transition-all duration-500">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-blue-500/20 to-indigo-600/20 rounded-2xl mb-8 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i data-lucide="shield-check" class="w-8 h-8 text-blue-400"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">{{ __('messages.features_secure') }}</h3>
                    <p class="text-slate-400 leading-relaxed">{{ __('messages.features_secure_desc') }}</p>
                </article>

                <!-- Feature 3 -->
                <article class="glass-card p-8 rounded-3xl group hover:border-indigo-500/30 transition-all duration-500">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-indigo-500/20 to-purple-600/20 rounded-2xl mb-8 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i data-lucide="code-2" class="w-8 h-8 text-indigo-400"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">{{ __('messages.features_api') }}</h3>
                    <p class="text-slate-400 leading-relaxed">{{ __('messages.features_api_desc') }}</p>
                </article>
            </div>
        </section>

        <!-- Database Status -->
        <section class="pb-32 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" aria-labelledby="db-status">
            <div
                class="glass-card rounded-2xl p-8 max-w-2xl mx-auto flex flex-col md:flex-row items-center gap-6 border-slate-800/50">
                <div class="flex-shrink-0">
                    <?php if ($status == 'success'): ?>
                    <div class="w-16 h-16 bg-emerald-500/10 rounded-full flex items-center justify-center">
                        <i data-lucide="database" class="w-8 h-8 text-emerald-500"></i>
                    </div>
                    <?php else: ?>
                    <div class="w-16 h-16 bg-rose-500/10 rounded-full flex items-center justify-center">
                        <i data-lucide="database-zap" class="w-8 h-8 text-rose-500"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex-grow text-center md:text-left">
                    <h2 id="db-status" class="text-xl font-bold text-white mb-1">
                        <?php echo $status == 'success' ? __('messages.db_connected') : __('messages.db_error'); ?>
                    </h2>
                    <p class="text-slate-400 text-sm">
                        <?php echo $status == 'success' ? __('messages.db_ready') : __('messages.db_check'); ?>
                    </p>
                </div>
                <div>
                    <a href="{{ url('_system/diagnose') }}"
                        class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm font-semibold transition-colors border border-slate-700">
                        Diagnostics
                    </a>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Refresh icons on page load
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
@endsection
