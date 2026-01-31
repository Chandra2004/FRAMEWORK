@extends('Internal::layout')

@section('terminal-content')
    <div class="mb-8">
        <div class="flex items-center gap-2 text-cyan-400 mb-2">
            <span>$</span>
            <span class="text-white">framework --list-commands</span>
            <span class="w-2 h-4 bg-cyan-500 cursor-blink ml-1"></span>
        </div>
        <p class="text-slate-500 italic">Welcome to the system maintenance console. Please select an operation from the
            options below.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
        <!-- Database Section -->
        <div>
            <div class="text-emerald-500 font-bold uppercase text-xs tracking-widest mb-4 flex items-center gap-2">
                <i data-lucide="database" class="w-3.5 h-3.5"></i>
                Database Management
            </div>
            <ul class="space-y-3">
                <li>
                    <a href="{{ url('_system/migrate') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">01.</span>
                        <div>
                            <span class="text-slate-300 group-hover:text-white font-bold transition-colors">migrate</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Run pending database migrations</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/migrate/rollback') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-amber-500 font-bold transition-colors">1a.</span>
                        <div>
                            <span
                                class="text-slate-300 group-hover:text-white font-bold transition-colors">migrate:rollback</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Revert last migration batch</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/migrate/fresh') }}" class="group flex items-start gap-4"
                        onclick="return confirm('DANGER ZONE: DATA LOSS WARNING!\n\nThis will DELETE ALL TABLES and DATA in your database.\nAction cannot be undone.\n\nAre you sure you want to proceed?')">
                        <span class="text-slate-600 group-hover:text-rose-500 font-bold transition-colors">1b.</span>
                        <div>
                            <span
                                class="text-slate-300 group-hover:text-white font-bold transition-colors">migrate:fresh</span>
                            <p class="text-[11px] text-slate-500 mt-0.5 text-rose-500/70 group-hover:text-rose-400">⚠️ Drop
                                all tables & re-migrate</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/seed') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">02.</span>
                        <div>
                            <span class="text-slate-300 group-hover:text-white font-bold transition-colors">db:seed</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Seed database with initial data</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/test-connection') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">03.</span>
                        <div>
                            <span class="text-slate-300 group-hover:text-white font-bold transition-colors">db:test</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Test database connection details</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Maintenance Section -->
        <div>
            <div class="text-amber-500 font-bold uppercase text-xs tracking-widest mb-4 flex items-center gap-2">
                <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                Maintenance & Cache
            </div>
            <ul class="space-y-3">
                <li>
                    <a href="{{ url('_system/optimize') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">04.</span>
                        <div>
                            <span class="text-slate-300 group-hover:text-white font-bold transition-colors">optimize</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Clear views and reset OpCache</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/clear-cache') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">05.</span>
                        <div>
                            <span
                                class="text-slate-300 group-hover:text-white font-bold transition-colors">cache:clear</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Remove manual cache files and logs</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/storage-link') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">06.</span>
                        <div>
                            <span
                                class="text-slate-300 group-hover:text-white font-bold transition-colors">storage:link</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Create storage symbolic link</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/asset-publish') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">07.</span>
                        <div>
                            <span
                                class="text-slate-300 group-hover:text-white font-bold transition-colors">asset:publish</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Copy resources to public/assets</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Monitoring Section -->
        <div>
            <div class="text-blue-500 font-bold uppercase text-xs tracking-widest mb-4 flex items-center gap-2">
                <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                Monitoring & Diagnostics
            </div>
            <ul class="space-y-3">
                <li>
                    <a href="{{ url('_system/diagnose') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">07.</span>
                        <div>
                            <span class="text-slate-300 group-hover:text-white font-bold transition-colors">diagnose</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Check Session, CSRF and DB details</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/logs') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">08.</span>
                        <div>
                            <span class="text-slate-300 group-hover:text-white font-bold transition-colors">logs</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">View last 50 lines of app.log</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/health') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">09.</span>
                        <div>
                            <span class="text-slate-300 group-hover:text-white font-bold transition-colors">health</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">System health status (JSON)</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Information Section -->
        <div>
            <div class="text-purple-500 font-bold uppercase text-xs tracking-widest mb-4 flex items-center gap-2">
                <i data-lucide="info" class="w-3.5 h-3.5"></i>
                System Information
            </div>
            <ul class="space-y-3">
                <li>
                    <a href="{{ url('_system/routes') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">10.</span>
                        <div>
                            <span class="text-slate-300 group-hover:text-white font-bold transition-colors">routes</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">List all registered routes</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/status') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">11.</span>
                        <div>
                            <span class="text-slate-300 group-hover:text-white font-bold transition-colors">status</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Check PHP & Extension status</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/phpinfo') }}" class="group flex items-start gap-4">
                        <span class="text-slate-600 group-hover:text-cyan-400 font-bold transition-colors">12.</span>
                        <div>
                            <span class="text-slate-300 group-hover:text-white font-bold transition-colors">phpinfo</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Display complete PHP configuration</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="mt-12 pt-8 border-t border-slate-800/50">
        <div class="bg-rose-500/10 border border-rose-500/20 p-4 rounded-xl flex gap-4">
            <i data-lucide="alert-triangle" class="w-6 h-6 text-rose-500 shrink-0"></i>
            <div class="text-xs">
                <span class="text-rose-400 font-bold uppercase block mb-1 underline">Security Warning</span>
                <p class="text-slate-400 leading-relaxed">
                    These tools affect core system components. Always disable <code
                        class="bg-slate-950 px-1 rounded text-rose-300">ALLOW_WEB_MIGRATION</code> in your <code
                        class="text-slate-300">.env</code> file after maintenance.
                </p>
            </div>
        </div>
    </div>
@endsection
