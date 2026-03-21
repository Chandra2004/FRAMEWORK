@extends('Internal::layout')

@section('terminal-content')
    <div class="mb-8 flex justify-between items-start">
        <div>
            <div class="flex items-center gap-2 text-cyan-400 mb-2">
                <span>$</span>
                <span class="text-white">framework --all-in-one --v5.0.1</span>
                <span class="w-2 h-4 bg-cyan-500 cursor-blink ml-1"></span>
            </div>
            <p class="text-slate-500 italic text-xs">Premium Web Command Center. Mode: <span class="text-emerald-500 font-bold uppercase">{{ strtoupper(\TheFramework\App\Core\Config::get('APP_ENV', 'local')) }}</span></p>
        </div>
        
        <!-- Global Quick Actions -->
        <div class="flex gap-2">
            @php $isDown = file_exists(BASE_PATH . '/storage/framework/down'); @endphp
            @if($isDown)
                <a href="{{ url('_system/maintenance/up') }}" class="px-3 py-1.5 bg-emerald-500/10 border border-emerald-500/50 text-emerald-400 text-[10px] font-bold rounded flex items-center gap-2 hover:bg-emerald-500 hover:text-white transition-all">
                    <i data-lucide="play" class="w-3 h-3"></i> ACTIVATE SITE
                </a>
            @else
                <a href="{{ url('_system/maintenance/down') }}" onclick="return confirm('DANGER: This will put the site into Maintenance Mode (503). Continue?')" class="px-3 py-1.5 bg-rose-500/10 border border-rose-500/50 text-rose-400 text-[10px] font-bold rounded flex items-center gap-2 hover:bg-rose-500 hover:text-white transition-all">
                    <i data-lucide="pause" class="w-3 h-3"></i> MAINTENANCE MODE
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- 1. DATABASE CONTROL -->
        <div class="bg-slate-900/40 p-5 rounded-2xl border border-slate-800/50 hover:border-emerald-500/30 transition-all group">
            <div class="text-emerald-500 font-bold uppercase text-[10px] tracking-widest mb-4 flex items-center justify-between">
                <span class="flex items-center gap-2"><i data-lucide="database" class="w-3.5 h-3.5"></i> DATABASE</span>
                <span class="text-slate-700 group-hover:text-emerald-500/40 transition-colors">01-07</span>
            </div>
            <ul class="space-y-4">
                <li>
                    <a href="{{ url('_system/migrate') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors underline decoration-slate-800 group-hover/item:decoration-emerald-500 underline-offset-4">migrate</span>
                        <p class="text-[10px] text-slate-500 mt-1">Jalankan database migration</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/migrate/status') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">migrate:status</span>
                        <p class="text-[10px] text-slate-500 mt-1">Status database migration</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/migrate/rollback') }}" class="group/item block" onclick="return confirm('UNDO last migration?')">
                        <span class="text-slate-300 group-hover/item:text-amber-500 text-xs font-bold block transition-colors">migrate:rollback</span>
                        <p class="text-[10px] text-slate-500 mt-1">Rollback batch terakhir</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/migrate/fresh') }}" class="group/item block" onclick="return confirm('DROP ALL TABLES?')">
                        <span class="text-rose-500 group-hover/item:text-rose-400 text-xs font-bold block transition-colors">migrate:fresh</span>
                        <p class="text-[10px] text-slate-500 mt-1">Hapus SEMUA tabel & run</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/seed') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">db:seed</span>
                        <p class="text-[10px] text-slate-500 mt-1">Jalankan seeder database</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/schema') }}" class="group/item block text-cyan-400/80">
                        <span class="text-cyan-400/80 group-hover/item:text-cyan-300 text-xs font-bold block transition-colors flex items-center gap-1.5">
                           db:schema <i data-lucide="zoom-in" class="w-3 h-3"></i>
                        </span>
                        <p class="text-[10px] text-slate-500 mt-1">Inspeksi tabel database</p>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 2. OPTIMIZE & CACHE -->
        <div class="bg-slate-900/40 p-5 rounded-2xl border border-slate-800/50 hover:border-amber-500/30 transition-all group">
            <div class="text-amber-500 font-bold uppercase text-[10px] tracking-widest mb-4 flex items-center justify-between">
                <span class="flex items-center gap-2"><i data-lucide="zap" class="w-3.5 h-3.5"></i> PERFORMANCE</span>
                <span class="text-slate-700 group-hover:text-amber-500/40 transition-colors">08-16</span>
            </div>
            <ul class="space-y-4">
                <li>
                    <a href="{{ url('_system/optimize/clear') }}" class="group/item block" onclick="return confirm('Hard clear all caches?')">
                        <span class="text-slate-300 group-hover/item:text-amber-400 text-xs font-bold block transition-colors">optimize:clear</span>
                        <p class="text-[10px] text-slate-500 mt-1">Hapus SEMUA cache & session</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/view/clear') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">view:clear</span>
                        <p class="text-[10px] text-slate-500 mt-1">Hapus compiled Blade views</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/route/cache') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">route:cache</span>
                        <p class="text-[10px] text-slate-500 mt-1">Cache file rute (Production)</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/route/clear') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">route:clear</span>
                        <p class="text-[10px] text-slate-500 mt-1">Hapus file cache rute</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/config/cache') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">config:cache</span>
                        <p class="text-[10px] text-slate-500 mt-1">Cache konfigurasi .env</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/config/clear') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">config:clear</span>
                        <p class="text-[10px] text-slate-500 mt-1">Hapus file cache config</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/cache/clear') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">cache:clear</span>
                        <p class="text-[10px] text-slate-500 mt-1">Hapus semua cache aplikasi</p>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 3. MONITORING & HEALTH -->
        <div class="bg-slate-900/40 p-5 rounded-2xl border border-slate-800/50 hover:border-blue-500/30 transition-all group">
            <div class="text-blue-500 font-bold uppercase text-[10px] tracking-widest mb-4 flex items-center justify-between">
                <span class="flex items-center gap-2"><i data-lucide="activity" class="w-3.5 h-3.5"></i> MONITORING</span>
                <span class="text-slate-700 group-hover:text-blue-500/40 transition-colors">17-23</span>
            </div>
            <ul class="space-y-4">
                <li>
                    <a href="{{ url('_system/env') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-blue-400 text-xs font-bold block transition-colors">env</span>
                        <p class="text-[10px] text-slate-500 mt-1">Cek Environment aplikasi</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/health') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">health</span>
                        <p class="text-[10px] text-slate-500 mt-1">System health status (JSON)</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/test') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-emerald-400 text-xs font-bold block transition-colors flex items-center gap-1.5">
                            test <i data-lucide="check-circle" class="w-3 h-3"></i>
                        </span>
                        <p class="text-[10px] text-slate-500 mt-1">Run Unit & Feature Tests</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/diagnose') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">diagnose</span>
                        <p class="text-[10px] text-slate-500 mt-1">Deep analysis state sitem</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/status') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">status</span>
                        <p class="text-[10px] text-slate-500 mt-1">Check PHP & Extensions</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/my-ip') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">my-ip</span>
                        <p class="text-[10px] text-slate-500 mt-1">Tampilkan IP saya saat ini</p>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 4. DEV TOOLS & INFO -->
        <div class="bg-slate-900/40 p-5 rounded-2xl border border-slate-800/50 hover:border-purple-500/30 transition-all group">
            <div class="text-purple-500 font-bold uppercase text-[10px] tracking-widest mb-4 flex items-center justify-between">
                <span class="flex items-center gap-2"><i data-lucide="code" class="w-3.5 h-3.5"></i> DEV TOOLS</span>
                <span class="text-slate-700 group-hover:text-purple-500/40 transition-colors">24-30</span>
            </div>
            <ul class="space-y-4">
                <li>
                    <a href="{{ url('_system/tinker') }}" class="group/item block">
                        <span class="text-warning-400 group-hover/item:text-warning-300 text-xs font-bold block transition-colors flex items-center gap-1.5">
                            tinker <i data-lucide="terminal" class="w-3 h-3"></i>
                        </span>
                        <p class="text-[10px] text-slate-500 mt-1">Interactive Web REPLShell</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/routes') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">route:list</span>
                        <p class="text-[10px] text-slate-500 mt-1">Tampilkan daftar rute premium</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/logs') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">logs</span>
                        <p class="text-[10px] text-slate-500 mt-1">Lihat 50 baris terakhir log</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/storage/link') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">storage:link</span>
                        <p class="text-[10px] text-slate-500 mt-1">Buat symbolic link storage</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/asset/publish') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">asset:publish</span>
                        <p class="text-[10px] text-slate-500 mt-1">Push resource ke public/assets</p>
                    </a>
                </li>
                <li>
                    <a href="{{ url('_system/phpinfo') }}" class="group/item block">
                        <span class="text-slate-300 group-hover/item:text-white text-xs font-bold block transition-colors">phpinfo</span>
                        <p class="text-[10px] text-slate-500 mt-1">Full PHP Configuration</p>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Security Guard -->
    <div class="mt-12 pt-6 border-t border-slate-800/50 flex flex-wrap gap-4 items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="text-[10px] text-slate-500 uppercase tracking-tighter">System is Secure. WAF Active.</span>
        </div>
        <div class="bg-rose-500/5 px-4 py-2 rounded-lg border border-rose-500/10 max-w-md">
            <p class="text-[9px] text-slate-500 leading-relaxed text-center">
                 🚨 <b class="text-rose-400 uppercase">Warning:</b> All actions logged with IP <b>{{ $_SERVER['REMOTE_ADDR'] }}</b>. Improper use may result in data loss or service disruption.
            </p>
        </div>
    </div>
@endsection
