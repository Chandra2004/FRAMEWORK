@extends('layout')

@section('terminal-content')
    <div class="mb-6">
        <a href="{{ url('_system') }}"
            class="inline-flex items-center gap-2 text-xs text-slate-500 hover:text-cyan-400 transition-colors mb-4">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            Back to Dashboard
        </a>

        <div class="flex items-center gap-2 text-cyan-400 mb-2">
            <span>$</span>
            <span class="text-white">framework {{ $command ?? 'run-command' }}</span>
            <span class="w-2 h-4 bg-cyan-500 cursor-blink ml-1"></span>
        </div>
    </div>

    <div class="bg-slate-950 p-6 rounded-xl border border-slate-800/50 shadow-inner">
        <pre class="whitespace-pre-wrap text-slate-300 font-mono text-sm leading-relaxed">{{ $output }}</pre>
    </div>

    @if (isset($success) && $success)
        <div class="mt-6 flex items-center gap-2 text-emerald-500 font-bold animate-pulse">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <span>Command executed successfully.</span>
        </div>
    @endif

    @if (isset($error) && $error)
        <div class="mt-6 flex items-center gap-2 text-rose-500 font-bold">
            <i data-lucide="x-circle" class="w-5 h-5"></i>
            <span>Command failed with errors.</span>
        </div>
    @endif

    <div
        class="mt-10 pt-6 border-t border-slate-800/30 flex justify-between items-center text-[10px] text-slate-600 font-bold uppercase tracking-widest">
        <span>Process Finished</span>
        <span>ID: #{{ substr(md5(time()), 0, 8) }}</span>
    </div>
@endsection
