@extends('Internal::layout')

@section('terminal-content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
                <span class="text-emerald-500">◆</span>
                System Status
            </h2>
            <p class="text-slate-400 text-sm">Real-time server environment configuration & health check.</p>
        </div>
        <div class="text-right">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest block mb-1">PHP Version</span>
            <span class="text-2xl font-mono text-cyan-400 font-bold">{{ $php_version }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Server Info -->
        <div class="col-span-1 md:col-span-2 bg-slate-900 border border-slate-800 rounded-xl p-6">
            <div class="text-xs text-slate-500 font-bold uppercase tracking-widest mb-4">Server Environment</div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Server Software</label>
                    <div class="font-mono text-sm text-white break-words">{{ $server }}</div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Memory Limit</label>
                        <div class="font-mono text-sm text-amber-400">{{ $memory_limit }}</div>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Upload Max</label>
                        <div class="font-mono text-sm text-emerald-400">{{ $upload_max }}</div>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">POST Max</label>
                        <div class="font-mono text-sm text-cyan-400">{{ $post_max }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div
            class="bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 rounded-xl p-6 flex flex-col justify-center items-center text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-500/10 flex items-center justify-center mb-4">
                <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500"></i>
            </div>
            <div class="text-white font-bold text-lg">System Healthy</div>
            <p class="text-slate-500 text-xs mt-1">All core requirements met</p>
        </div>
    </div>

    <!-- Extensions -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 bg-slate-950/50 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-300">PHP Extensions</h3>
            <span class="text-xs bg-slate-800 text-slate-400 px-2 py-1 rounded">{{ count($extensions) }} Checked</span>
        </div>

        <div class="divide-y divide-slate-800/50">
            @foreach ($extensions as $ext => $enabled)
                <div class="px-6 py-3 flex items-center justify-between hover:bg-slate-800/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-2 h-2 rounded-full {{ $enabled ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-rose-500' }}">
                        </div>
                        <span
                            class="font-mono text-sm {{ $enabled ? 'text-slate-300' : 'text-slate-500 line-through' }}">{{ $ext }}</span>
                    </div>
                    <span class="text-xs font-bold {{ $enabled ? 'text-emerald-500' : 'text-rose-500' }} uppercase">
                        {{ $enabled ? 'Enabled' : 'Missing' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-8 text-center pt-8 border-t border-slate-800/50">
        <a href="{{ url('_system') }}"
            class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-sm font-medium">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back to Dashboard</span>
        </a>
    </div>
@endsection
