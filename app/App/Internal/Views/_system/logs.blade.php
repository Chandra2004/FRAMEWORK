@extends('Internal::layout')

@section('terminal-content')
    <div class="flex flex-col h-[calc(100vh-12rem)]">
        <!-- Header -->
        <div class="shrink-0 mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
                    <span class="text-amber-500">◆</span>
                    Application Logs
                </h2>
                <div class="flex items-center gap-2 text-slate-500 text-xs font-mono">
                    <i data-lucide="file-text" class="w-3 h-3"></i>
                    <span>{{ str_replace(ROOT_DIR, '', $path) }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ url('_system/logs') }}"
                    class="bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-300 px-3 py-1.5 rounded text-xs font-bold transition-colors flex items-center gap-2">
                    <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                    Refresh
                </a>
                <a href="{{ url('_system/clear-cache') }}"
                    class="bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white px-3 py-1.5 rounded text-xs font-bold transition-colors border border-rose-500/20 hover:border-transparent flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                    Clear All
                </a>
            </div>
        </div>

        <!-- Log Viewer -->
        <div class="flex-1 bg-[#0d1117] rounded-xl border border-slate-800 overflow-hidden flex flex-col shadow-2xl">
            <div class="bg-slate-900/50 px-4 py-2 border-b border-slate-800 flex items-center justify-between text-xs">
                <span class="text-slate-400">Viewing last {{ count($logs) }} lines</span>
                <div class="flex gap-1.5">
                    <div class="w-2.5 h-2.5 rounded-full bg-rose-500/20 border border-rose-500/50"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-amber-500/20 border border-amber-500/50"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500/20 border border-emerald-500/50"></div>
                </div>
            </div>

            <div class="flex-1 overflow-auto p-4 font-mono text-xs leading-relaxed space-y-1 custom-scrollbar">
                @forelse($logs as $line)
                    @php
                        // Simple syntax coloring
                        $colorClass = 'text-slate-300';
                        $bgClass = '';
                        $icon = '';

                        if (str_contains($line, '.ERROR:')) {
                            $colorClass = 'text-rose-400';
                            $bgClass = 'bg-rose-500/5';
                            $icon = '❌';
                        } elseif (str_contains($line, '.WARNING:')) {
                            $colorClass = 'text-amber-400';
                            $icon = '⚠️';
                        } elseif (str_contains($line, '.INFO:')) {
                            $colorClass = 'text-cyan-400';
                            $icon = 'ℹ️';
                        } elseif (str_contains($line, 'Stack trace:')) {
                            $colorClass = 'text-slate-500 italic pl-4';
                        } elseif (str_contains($line, '#')) {
                            $colorClass = 'text-slate-500 pl-8';
                        }
                    @endphp
                    <div
                        class="whitespace-pre-wrap {{ $colorClass }} {{ $bgClass }} px-2 py-0.5 -mx-2 rounded hover:bg-slate-800/30 transition-colors border-l-2 border-transparent hover:border-slate-700">
                        <span class="opacity-50 select-none mr-2 w-4 inline-block text-right">{{ $loop->iteration }}</span>
                        @if ($icon)
                            <span class="mr-1 select-none">{{ $icon }}</span>
                        @endif
                        {!! nl2br(htmlspecialchars($line)) !!}
                    </div>
                @empty
                    <div class="h-full flex flex-col items-center justify-center text-slate-600">
                        <i data-lucide="check-circle" class="w-12 h-12 mb-4 opacity-50"></i>
                        <p>No logs found. Everything looks clean!</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ url('_system') }}"
                class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-300 transition-colors text-xs font-medium">
                <kbd class="bg-slate-800 px-1.5 py-0.5 rounded text-[10px] font-sans">ESC</kbd>
                <span>Back to Console</span>
            </a>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            bg-slate-950;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 5px;
            border: 2px solid #0d1117;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>
@endsection
