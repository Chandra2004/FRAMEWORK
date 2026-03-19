@extends('Internal::layout')

@section('terminal-content')
    <div class="mb-6">
        <a href="{{ url('_system') }}"
            class="inline-flex items-center gap-2 text-xs text-slate-500 hover:text-cyan-400 transition-colors mb-4">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            Back to Dashboard
        </a>

        <div class="flex items-center gap-2 text-cyan-400 mb-2">
            <span>$</span>
            <span class="text-white">framework backup --interactive</span>
            <span class="w-2 h-4 bg-cyan-500 cursor-blink ml-1"></span>
        </div>
        <p class="text-slate-500 italic text-xs">Select a backup operation below. Files will be generated and downloaded automatically.</p>
    </div>

    {{-- Database Info --}}
    <div class="bg-slate-950 p-5 rounded-xl border border-slate-800/50 shadow-inner mb-6">
        <div class="text-emerald-500 font-bold uppercase text-xs tracking-widest mb-4 flex items-center gap-2">
            <i data-lucide="database" class="w-3.5 h-3.5"></i>
            Database Backup
        </div>

        @if(!empty($databases))
            <div class="text-xs text-slate-400 mb-3">
                <span class="text-slate-500">Connected to:</span>
                <span class="text-cyan-400 font-bold">{{ $currentDb ?? 'N/A' }}</span>
                <span class="text-slate-600 mx-2">•</span>
                <span class="text-slate-500">{{ count($tables ?? []) }} tables found</span>
            </div>

            {{-- Tables Overview --}}
            @if(!empty($tables))
                <div class="bg-slate-900/50 rounded-lg p-3 mb-4 max-h-48 overflow-y-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-slate-500 uppercase tracking-widest border-b border-slate-800">
                                <th class="text-left py-1.5 px-2">Table</th>
                                <th class="text-right py-1.5 px-2">Rows</th>
                                <th class="text-right py-1.5 px-2">Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tables as $tbl)
                                <tr class="border-b border-slate-800/30 hover:bg-slate-800/20">
                                    <td class="py-1.5 px-2 text-slate-300">
                                        <i data-lucide="table" class="w-3 h-3 inline text-slate-600 mr-1"></i>
                                        {{ $tbl['name'] }}
                                    </td>
                                    <td class="py-1.5 px-2 text-right text-cyan-400">{{ number_format($tbl['rows']) }}</td>
                                    <td class="py-1.5 px-2 text-right text-slate-500">{{ $tbl['size'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Download Buttons --}}
            <div class="flex flex-wrap gap-3">
                <a href="{{ url('_system/backup/database?db=' . urlencode($currentDb ?? '')) }}"
                    class="group inline-flex items-center gap-2 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 px-4 py-2.5 rounded-lg text-xs font-bold transition-all border border-emerald-500/20 hover:border-emerald-500/40"
                    onclick="this.innerHTML='<span class=\'animate-pulse\'>⏳ Generating SQL dump...</span>'; return true;">
                    <i data-lucide="download" class="w-4 h-4 group-hover:animate-bounce"></i>
                    Download {{ $currentDb }}.sql
                </a>

                @if(count($databases) > 1)
                    <div class="relative" x-data="{ open: false }">
                        <button onclick="document.getElementById('db-dropdown').classList.toggle('hidden')"
                            class="inline-flex items-center gap-2 bg-slate-800/50 hover:bg-slate-800 text-slate-300 px-4 py-2.5 rounded-lg text-xs font-bold transition-all border border-slate-700">
                            <i data-lucide="chevron-down" class="w-3 h-3"></i>
                            Other Database
                        </button>
                        <div id="db-dropdown" class="hidden absolute top-full mt-1 left-0 bg-slate-900 border border-slate-700 rounded-lg shadow-2xl z-10 min-w-[200px]">
                            @foreach($databases as $db)
                                <a href="{{ url('_system/backup?db=' . urlencode($db)) }}"
                                    class="block px-4 py-2 text-xs text-slate-300 hover:bg-slate-800 hover:text-white transition-all {{ $db === $currentDb ? 'text-cyan-400 bg-slate-800/50' : '' }}">
                                    <i data-lucide="database" class="w-3 h-3 inline mr-1"></i>
                                    {{ $db }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="text-rose-400 text-xs flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                Database connection failed. Check your .env configuration.
            </div>
        @endif
    </div>

    {{-- Application Backup --}}
    <div class="bg-slate-950 p-5 rounded-xl border border-slate-800/50 shadow-inner mb-6">
        <div class="text-purple-500 font-bold uppercase text-xs tracking-widest mb-4 flex items-center gap-2">
            <i data-lucide="archive" class="w-3.5 h-3.5"></i>
            Application Backup
        </div>

        <p class="text-xs text-slate-400 mb-4 leading-relaxed">
            Package the entire application into a <code class="bg-slate-900 px-1.5 py-0.5 rounded text-cyan-300">.zip</code> archive.
            Includes source code, configs, views, and optionally the database dump.
        </p>

        <div class="bg-slate-900/50 rounded-lg p-3 mb-4">
            <div class="text-[10px] text-slate-500 uppercase font-bold tracking-widest mb-2">Archive will include:</div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs text-slate-400">
                <span><i data-lucide="folder" class="w-3 h-3 inline text-cyan-600 mr-1"></i> app/</span>
                <span><i data-lucide="folder" class="w-3 h-3 inline text-cyan-600 mr-1"></i> bootstrap/</span>
                <span><i data-lucide="folder" class="w-3 h-3 inline text-cyan-600 mr-1"></i> config/</span>
                <span><i data-lucide="folder" class="w-3 h-3 inline text-cyan-600 mr-1"></i> database/</span>
                <span><i data-lucide="folder" class="w-3 h-3 inline text-cyan-600 mr-1"></i> public/</span>
                <span><i data-lucide="folder" class="w-3 h-3 inline text-cyan-600 mr-1"></i> storage/ *</span>
                <span><i data-lucide="folder" class="w-3 h-3 inline text-cyan-600 mr-1"></i> private-uploads/</span>
                <span><i data-lucide="folder" class="w-3 h-3 inline text-emerald-600 mr-1"></i> .idx/ & .vscode/</span>
                <span><i data-lucide="file-json" class="w-3 h-3 inline text-amber-600 mr-1"></i> composer.json/.lock</span>
                <span><i data-lucide="settings" class="w-3 h-3 inline text-rose-400 mr-1"></i> .env & .htaccess</span>
                <span><i data-lucide="terminal" class="w-3 h-3 inline text-slate-400 mr-1"></i> artisan & index.php</span>
                <span><i data-lucide="file-text" class="w-3 h-3 inline text-slate-400 mr-1"></i> .gitignore</span>
            </div>
            <div class="text-[10px] text-slate-600 mt-2 italic">
                * Storage includes structure & app data, but excludes logs, cache, & sessions.
                <br>Excludes: vendor/, node_modules/, /storage/logs/
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ url('_system/backup/app') }}"
                class="group inline-flex items-center gap-2 bg-purple-600/20 hover:bg-purple-600/30 text-purple-400 px-4 py-2.5 rounded-lg text-xs font-bold transition-all border border-purple-500/20 hover:border-purple-500/40"
                onclick="this.innerHTML='<span class=\'animate-pulse\'>⏳ Packaging application...</span>'; return true;">
                <i data-lucide="package" class="w-4 h-4 group-hover:animate-bounce"></i>
                Download App Backup (.zip)
            </a>

            <a href="{{ url('_system/backup/full') }}"
                class="group inline-flex items-center gap-2 bg-amber-600/20 hover:bg-amber-600/30 text-amber-400 px-4 py-2.5 rounded-lg text-xs font-bold transition-all border border-amber-500/20 hover:border-amber-500/40"
                onclick="this.innerHTML='<span class=\'animate-pulse\'>⏳ Full backup in progress...</span>'; return true;">
                <i data-lucide="hard-drive-download" class="w-4 h-4 group-hover:animate-bounce"></i>
                Full Backup (App + DB .zip)
            </a>
        </div>
    </div>

    {{-- Warning --}}
    <div class="bg-amber-500/10 border border-amber-500/20 p-4 rounded-xl flex gap-4">
        <i data-lucide="shield-alert" class="w-6 h-6 text-amber-500 shrink-0"></i>
        <div class="text-xs">
            <span class="text-amber-400 font-bold uppercase block mb-1">Backup Security Notice</span>
            <p class="text-slate-400 leading-relaxed">
                Backup files contain sensitive data including database credentials and application secrets.
                Downloaded files are <strong class="text-amber-300">NOT encrypted</strong>. Store them securely and delete temporary backup files from the server after download.
            </p>
        </div>
    </div>
@endsection
