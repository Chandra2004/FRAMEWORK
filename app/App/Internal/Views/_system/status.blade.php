@extends('Internal::layout')

@section('terminal-content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
                <span class="text-emerald-500">◆</span>
                Infrastructure Audit
            </h2>
            <p class="text-slate-400 text-sm italic">Platform compatibility and server resource monitoring.</p>
        </div>
        <div class="text-right">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest block mb-1">Server Time</span>
            <span class="text-sm font-mono text-cyan-400 font-bold">{{ $server_time }}</span>
        </div>
    </div>

    <!-- Core Engine & Limits -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- PHP Engine -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-emerald-500/30 transition-all">
            <div class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                 <i data-lucide="cpu" class="w-3.5 h-3.5 text-emerald-500"></i> PHP Engine & Environment
            </div>
            <div class="space-y-3">
                @foreach($engine as $label => $value)
                <div class="flex justify-between items-center py-2 border-b border-slate-800/50 last:border-0">
                    <span class="text-xs text-slate-400">{{ $label }}</span>
                    <span class="text-xs font-mono text-white">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Resource Limits -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-amber-500/30 transition-all">
            <div class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                 <i data-lucide="gauge" class="w-3.5 h-3.5 text-amber-500"></i> Resource & Runtime Limits
            </div>
            <div class="grid grid-cols-2 gap-x-8 gap-y-3">
                @foreach($limits as $label => $value)
                <div class="py-2 border-b border-slate-800/50">
                    <span class="block text-[10px] text-slate-500 mb-1">{{ $label }}</span>
                    <span class="text-xs font-mono text-amber-400 font-bold">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Security & Extension Audit -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
         <!-- Networking & Security -->
         <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 lg:col-span-1">
            <div class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mb-4">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 inline mr-1 text-blue-500"></i> Security & Network
            </div>
            <div class="space-y-4">
                @foreach($security as $label => $value)
                <div>
                     <span class="block text-[10px] text-slate-500 mb-1">{{ $label }}</span>
                     <span class="text-[11px] font-mono text-slate-300 break-words">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Extensions Audit -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden lg:col-span-2">
            <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/50 flex justify-between items-center">
                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em]">
                    <i data-lucide="package" class="w-3.5 h-3.5 inline mr-1 text-purple-500"></i> Extension Audit
                </div>
                <span class="text-[9px] bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-500/20">All Version Checked</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2">
                @foreach ($extensions as $ext => $data)
                <div class="px-6 py-3 flex items-center justify-between border-b border-slate-800 last:border-0 hover:bg-slate-800/30 transition-all">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-1.5 rounded-full {{ $data['enabled'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></div>
                        <span class="font-mono text-xs {{ $data['enabled'] ? 'text-slate-200' : 'text-slate-600' }}">{{ $ext }}</span>
                    </div>
                    <span class="text-[10px] font-mono font-bold text-slate-500">{{ $data['version'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Critical Function Audit (Hosting Compatibility) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Disabled Functions -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <div class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mb-4 flex items-center justify-between">
                <span><i data-lucide="zap-off" class="w-3.5 h-3.5 inline mr-1 text-rose-500"></i> Function availability audit</span>
                <span class="text-[9px] text-slate-600 italic">Hosting Compatibility Check</span>
            </div>
            <div class="grid grid-cols-2 gap-4">
                @foreach($function_audit as $func => $allowed)
                <div class="flex items-center justify-between p-2 rounded bg-slate-950/40">
                    <span class="text-[11px] font-mono {{ $allowed ? 'text-slate-300' : 'text-slate-600 line-through' }}">{{ $func }}()</span>
                    @if($allowed)
                        <i data-lucide="check" class="w-3 h-3 text-emerald-500"></i>
                    @else
                        <i data-lucide="x" class="w-3 h-3 text-rose-500"></i>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Path Audit -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <div class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mb-4">
                <i data-lucide="folder-tree" class="w-3.5 h-3.5 inline mr-1 text-cyan-500"></i> Path & Permission Audit
            </div>
            <div class="space-y-3">
                @foreach($path_audit as $label => $data)
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">{{ $label }}</span>
                    <div class="flex gap-2">
                        <span class="px-2 py-0.5 rounded {{ $data['exists'] ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500' }} text-[9px] font-bold">
                            {{ $data['exists'] ? 'EXISTS' : 'MISSING' }}
                        </span>
                        <span class="px-2 py-0.5 rounded {{ $data['writable'] ? 'bg-blue-500/10 text-blue-500' : 'bg-amber-500/10 text-amber-500' }} text-[9px] font-bold">
                            {{ $data['writable'] ? 'WRITABLE' : 'READ-ONLY' }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-8 text-center pt-8 border-t border-slate-800/50">
        <a href="{{ url('_system') }}"
            class="inline-flex items-center gap-2 text-slate-400 hover:text-cyan-400 transition-all text-sm font-medium group">
            <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
            <span>Return to Console</span>
        </a>
    </div>
@endsection
