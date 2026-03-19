@extends('Internal::layout')

@section('terminal-content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
                <span class="text-purple-500">◆</span>
                Registered Routes
            </h2>
            <p class="text-slate-400 text-sm">List of all active routes in the application.</p>
        </div>
        <div class="flex gap-2 text-xs">
            <span class="px-2 py-1 rounded bg-slate-800 text-slate-300 border border-slate-700">Total:
                {{ count($routes) }}</span>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
        <!-- Toolbar -->
        <div class="px-4 py-3 bg-slate-950/50 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                    <span class="text-slate-400">GET</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="text-slate-400">POST</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span class="text-slate-400">DELETE</span>
                </div>
            </div>
            <div class="relative">
                <input type="text" id="routeSearch" placeholder="Filter routes..."
                    class="bg-slate-900 text-slate-300 text-xs px-3 py-1.5 rounded border border-slate-700 focus:outline-none focus:border-cyan-500 w-48 transition-colors">
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-950 text-slate-500 uppercase font-bold text-[10px] tracking-wider">
                    <tr>
                        <th class="px-4 py-3 border-b border-slate-800 w-20">Method</th>
                        <th class="px-4 py-3 border-b border-slate-800">URI Path</th>
                        <th class="px-4 py-3 border-b border-slate-800">Handler / Controller</th>
                        <th class="px-4 py-3 border-b border-slate-800 w-32">Middleware</th>
                        <th class="px-4 py-3 border-b border-slate-800 text-right w-24">Type</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50 text-xs font-mono" id="routeTable">
                    @foreach ($routes as $route)
                        @php
                            $methodColor = match ($route['method']) {
                                'GET' => 'text-cyan-400 bg-cyan-400/10 border-cyan-400/20',
                                'POST' => 'text-amber-400 bg-amber-400/10 border-amber-400/20',
                                'PUT', 'PATCH' => 'text-emerald-400 bg-emerald-400/10 border-emerald-400/20',
                                'DELETE' => 'text-rose-400 bg-rose-400/10 border-rose-400/20',
                                default => 'text-slate-400 bg-slate-400/10 border-slate-400/20',
                            };
                        @endphp
                        <tr class="group hover:bg-slate-800/30 transition-colors route-row">
                            <td class="px-4 py-3 font-bold">
                                <span class="px-2 py-0.5 rounded border {{ $methodColor }} text-[10px]">
                                    {{ $route['method'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-white">
                                {{ $route['uri'] }}
                            </td>
                            <td class="px-4 py-3 text-slate-400">
                                {{ $route['handler'] }}
                            </td>
                            <td class="px-4 py-3 text-slate-500 italic">
                                {{ $route['middleware'] ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($route['type'] === 'System')
                                    <span
                                        class="text-[10px] text-purple-400 bg-purple-500/10 px-1.5 py-0.5 rounded border border-purple-500/20">SYSTEM</span>
                                @elseif($route['type'] === 'Asset')
                                    <span
                                        class="text-[10px] text-slate-400 bg-slate-500/10 px-1.5 py-0.5 rounded border border-slate-500/20">ASSET</span>
                                @else
                                    <span
                                        class="text-[10px] text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">APP</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 text-center pt-8 border-t border-slate-800/50">
        <a href="{{ url('_system') }}"
            class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-sm font-medium">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back to Dashboard</span>
        </a>
    </div>

    <script>
        // Simple filter script
        document.getElementById('routeSearch').addEventListener('keyup', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.route-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    </script>
@endsection
