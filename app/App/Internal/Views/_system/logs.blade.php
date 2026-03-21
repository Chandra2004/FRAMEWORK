@extends('Internal::layout')

@section('terminal-content')
    <div class="flex flex-col h-[calc(100vh-10rem)]">
        <!-- Header -->
        <div class="shrink-0 mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-white mb-1 flex items-center gap-2">
                    <span class="text-amber-500">◆</span>
                    System Event Logs
                </h2>
                <div class="flex items-center gap-2 text-slate-500 text-[10px] font-mono">
                    <i data-lucide="file-text" class="w-3 h-3 text-amber-500/50"></i>
                    <span>storage/logs/</span>
                    <span class="text-slate-300 font-bold">{{ $current_file }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                 <div class="bg-slate-900 border border-slate-800 rounded px-3 py-1.5 flex items-center gap-3">
                    <span class="text-[9px] text-slate-500 font-extrabold uppercase tracking-widest">SIZE</span>
                    <span class="text-[11px] font-mono text-cyan-400 font-bold">{{ $file_size }}</span>
                 </div>
                <a href="{{ url('_system/logs?file=' . $current_file) }}"
                    class="bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-300 px-3 py-1.5 rounded text-[10px] font-black transition-all flex items-center gap-2">
                    <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                    REFRESH
                </a>
            </div>
        </div>

        <div class="flex-1 flex gap-4 overflow-hidden">
            <!-- File Selector Side Panel (IDE Trace Style) -->
            <div class="w-72 shrink-0 flex flex-col bg-[#18181c] rounded-xl border border-slate-800/50 overflow-hidden shadow-2xl">
                <div class="px-4 py-2.5 bg-[#1e1e22] border-b border-dark-50/20 flex items-center justify-between shrink-0">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">LOGS HISTORY</span>
                    <span class="text-[9px] bg-dark-100 text-zinc-500 px-1.5 py-0.5 rounded font-bold">{{ count($all_files) }} FILES</span>
                </div>
                <div class="flex-1 overflow-y-auto pb-4 custom-scrollbar">
                    @foreach($all_files as $file)
                        <a href="{{ url('_system/logs?file=' . $file['name']) }}" 
                           class="group relative block px-4 py-3 border-b border-[#1e1e22] border-l-2 transition-all {{ $current_file === $file['name'] ? 'bg-[#1e1e22] border-l-amber-500' : 'border-l-transparent hover:bg-white/5' }}">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[11px] font-mono {{ $current_file === $file['name'] ? 'text-zinc-100 font-black' : 'text-zinc-500' }} truncate pr-2">
                                    {{ $file['name'] }}
                                </span>
                                @if($current_file === $file['name'])
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                @endif
                            </div>
                            <div class="flex justify-between items-center text-[9px] font-mono {{ $current_file === $file['name'] ? 'text-zinc-400' : 'text-zinc-600' }}">
                                <span>{{ $file['modified'] }}</span>
                                <span>{{ $file['size'] }}</span>
                            </div>
                            
                            @if($current_file === $file['name'])
                             <div class="absolute inset-y-0 right-0 w-1 bg-amber-500/20 pointer-events-none"></div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Log Content Viewer (IDE Code Snippet Style) -->
            <div class="flex-1 bg-[#1a1a1f] rounded-xl border border-slate-800/80 overflow-hidden shadow-2xl flex flex-col">
                <!-- Viewer Header -->
                <div class="px-5 py-2.5 border-b border-dark-50/20 flex items-center justify-between bg-[#1e1e22] shrink-0">
                    <div class="flex items-center">
                        <svg class="w-3.5 h-3.5 text-zinc-600 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="text-[11px] font-mono text-zinc-500">Viewing latest 200 entries of <span class="text-zinc-200">{{ $current_file }}</span></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[9px] font-black text-emerald-500/50 uppercase tracking-tighter">Live Monitor Active</span>
                        <div class="flex gap-1">
                            <div class="w-2 h-2 rounded-full bg-rose-500/20"></div>
                            <div class="w-2 h-2 rounded-full bg-amber-500/20"></div>
                            <div class="w-2 h-2 rounded-full bg-emerald-500/20"></div>
                        </div>
                    </div>
                </div>

                <!-- Terminal Output (Compact Lines) -->
                <div class="flex-1 overflow-auto custom-scrollbar-bold bg-[#0d1117]/80">
                    <div class="code-container py-2 min-w-full">
                        @if(empty($logs))
                            <div class="flex flex-col items-center justify-center py-24 text-zinc-600">
                                <i data-lucide="check-circle" class="w-12 h-12 mb-4 opacity-20"></i>
                                <p class="text-[11px] font-medium tracking-tight uppercase">Log stream empty.</p>
                            </div>
                        @else
                            @foreach($logs as $index => $line)
                                @php
                                    $revIndex = count($logs) - $index;
                                    $markerColor = 'border-transparent';
                                    $textColor = 'text-[#d4d4d8]';
                                    $rowBg = 'hover:bg-white/[0.03]';

                                    if (str_contains($line, '.ERROR:')) {
                                        $markerColor = 'border-rose-500';
                                        $textColor = 'text-rose-400';
                                        $rowBg = 'bg-rose-500/5 hover:bg-rose-500/10';
                                    } elseif (str_contains($line, '.WARNING:')) {
                                        $markerColor = 'border-amber-500';
                                        $textColor = 'text-amber-400';
                                        $rowBg = 'bg-amber-500/5 hover:bg-amber-500/10';
                                    } elseif (str_contains($line, '.INFO:')) {
                                        $markerColor = 'border-cyan-500';
                                        $textColor = 'text-cyan-400';
                                    } elseif (preg_match('/^#\d+/', trim($line)) || str_contains($line, 'Stack trace:')) {
                                        $textColor = 'text-zinc-600 italic';
                                    }
                                    
                                    // Parse parts if it's a standard log line
                                    $parsedLine = $line;
                                    if (preg_match('/^\[(.*?)\] (.*?): (.*)$/', $line, $matches)) {
                                        $timestamp = $matches[1];
                                        $level = $matches[2];
                                        $message = $matches[3];
                                        
                                        $levelColor = 'text-zinc-500';
                                        if (str_contains($level, 'ERROR')) $levelColor = 'text-rose-500 font-bold';
                                        elseif (str_contains($level, 'WARNING')) $levelColor = 'text-amber-500 font-bold';
                                        elseif (str_contains($level, 'INFO')) $levelColor = 'text-cyan-500 font-bold';
                                        
                                        $parsedLine = "<span class=\"text-zinc-600\">[$timestamp]</span> <span class=\"$levelColor\">$level</span>: <span class=\"$textColor\">$message</span>";
                                    }
                                @endphp
                                <div class="code-line group flex items-stretch {{ $rowBg }} transition-colors border-l-[3px] {{ $markerColor }}">
                                    <!-- Line Num -->
                                    <div class="line-num shrink-0 w-12 text-right pr-4 text-[10px] font-mono text-zinc-700 select-none border-r border-[#27272a] bg-[#1a1a1f]/20 group-hover:text-zinc-500">
                                        {{ $revIndex }}
                                    </div>
                                    <!-- Log Code -->
                                    {{-- class="line-code px-3 py-0.5 text-[12px] font-mono whitespace-pre -tracking-tight overflow-visible" --}}
                                    <div class="ml-3 whitespace-nowrap">
                                        {!! is_string($parsedLine) ? $parsedLine : htmlspecialchars($line) !!}
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="shrink-0 mt-6 text-center">
            <a href="{{ url('_system') }}"
                class="inline-flex items-center gap-2 text-slate-500 hover:text-cyan-400 transition-all text-xs font-black group">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-1 transition-all"></i>
                RETURN TO SYSTEM CONSOLE
            </a>
        </div>
    </div>

    <style>
        .code-line { line-height: 1.5; }
        .custom-scrollbar-bold::-webkit-scrollbar { width: 10px; height: 10px; }
        .custom-scrollbar-bold::-webkit-scrollbar-track { background: #0d1117; }
        .custom-scrollbar-bold::-webkit-scrollbar-thumb { background: #334155; border-radius: 5px; border: 2px solid #0d1117; }
        .custom-scrollbar-bold::-webkit-scrollbar-thumb:hover { background: #475569; }
        
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e1e22; border-radius: 2px; }
        
        /* Monospace font handling */
        .line-code { font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace; }
    </style>
@endsection
