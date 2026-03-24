@extends('Internal::layout')

@section('terminal-content')
    <div class="mb-6 flex items-end justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-600 flex items-center justify-center shadow-lg shadow-purple-900/40">
                <i data-lucide="route" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">Route Inventory</h2>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-[9px] text-slate-500 font-black uppercase tracking-widest border-r border-slate-800 pr-2">Build 5.2.1</span>
                    <span class="text-[10px] text-purple-400 font-mono">{{ array_sum(array_map('count', $categories)) }} total endpoints detected</span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <div class="relative group">
                <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-purple-400 transition-colors"></i>
                <input type="text" id="routeSearch" placeholder="Filter endpoints..."
                    class="bg-slate-900 border border-slate-800 text-slate-200 text-xs pl-9 pr-4 py-2 rounded-lg focus:outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/20 w-56 transition-all placeholder:text-slate-600">
            </div>
            <button onclick="toggleAll(true)" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-lg text-[10px] font-black text-slate-400 transition-all uppercase tracking-tighter">Expand</button>
            <button onclick="toggleAll(false)" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-lg text-[10px] font-black text-slate-400 transition-all uppercase tracking-tighter">Collapse</button>
        </div>
    </div>

    <!-- Accordion List -->
    <div class="space-y-2 mb-10">
        @php 
            $icons = ['APPLICATION' => 'layout', 'FILE STORAGE' => 'file-code', 'STATIC ASSETS' => 'image', 'SEO & SITEMAP' => 'globe', 'SYSTEM CONTROL' => 'settings'];
            $colors = ['APPLICATION' => 'emerald', 'FILE STORAGE' => 'blue', 'STATIC ASSETS' => 'amber', 'SEO & SITEMAP' => 'cyan', 'SYSTEM CONTROL' => 'purple'];
        @endphp

        @foreach($categories as $catName => $routes)
            @if(count($routes) > 0)
                <div class="accordion-item {{ $loop->first ? 'is-active' : '' }} border border-slate-800 rounded-xl overflow-hidden bg-slate-900/20 group" id="cat-{{ str_replace(' ', '-', $catName) }}">
                    <!-- Header -->
                    <button class="accordion-header w-full px-5 py-3.5 flex items-center justify-between hover:bg-slate-800/40 transition-all">
                        <div class="flex items-center gap-3">
                            <i data-lucide="{{ $icons[(string)$catName] ?? 'circle' }}" class="w-4 h-4 text-slate-500 group-[.is-active]:text-{{ $colors[(string)$catName] ?? 'purple' }}-400 transition-all"></i>
                            <h3 class="text-[11px] font-black group-[.is-active]:text-white text-slate-400 tracking-widest uppercase">{{ (string)$catName }}</h3>
                            <span class="text-[9px] bg-slate-800 text-slate-500 px-1.5 py-0.5 rounded font-bold">{{ count($routes) }}</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-700 group-[.is-active]:rotate-180 transition-all duration-300"></i>
                    </button>
                    
                    <!-- Content -->
                    <div class="accordion-content max-h-0 overflow-hidden transition-all duration-300 group-[.is-active]:max-h-[3000px] border-t border-slate-800/50">
                        <div class="bg-black/20 overflow-x-auto no-scrollbar">
                            <table class="w-full text-left border-collapse table-fixed">
                                <thead>
                                    <tr class="text-[9px] text-slate-600 font-black uppercase tracking-widest border-b border-slate-800/30">
                                        <th class="px-5 py-2 w-[110px]">Method</th>
                                        <th class="px-5 py-2 w-1/3">Path</th>
                                        <th class="px-5 py-2">Action / Controller</th>
                                        <th class="px-5 py-2 text-right w-[150px]">Metadata</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/20 text-[11px] font-mono">
                                    @foreach($routes as $route)
                                        @php
                                            $method = strtoupper($route['method']);
                                            $mColor = 'text-slate-500 bg-slate-500/10 border-slate-500/20';
                                            switch ($method) {
                                                case 'GET': $mColor = 'text-cyan-500 bg-cyan-500/10 border-cyan-500/20'; break;
                                                case 'POST': $mColor = 'text-amber-500 bg-amber-500/10 border-amber-500/20'; break;
                                                case 'PUT':
                                                case 'PATCH': $mColor = 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20'; break;
                                                case 'DELETE': $mColor = 'text-rose-500 bg-rose-500/10 border-rose-500/20'; break;
                                            }
                                            
                                            // Split Handler
                                            $parts = explode('@', $route['handler']);
                                            $shortHandler = basename(str_replace('\\', '/', $parts[0]));
                                            if (isset($parts[1])) $shortHandler .= '@' . $parts[1];
                                        @endphp
                                        <tr class="route-row hover:bg-purple-500/[0.03] transition-colors group/row">
                                            <td class="px-5 py-2.5">
                                                <span class="px-1.5 py-0.5 rounded border {{ $mColor }} text-[9px] font-black">{{ $method }}</span>
                                            </td>
                                            <td class="px-5 py-2.5">
                                                <div class="flex flex-col">
                                                    <span class="text-zinc-100 font-bold group-hover/row:text-purple-400 transition-colors break-all">
                                                        {!! preg_replace('/\{([a-zA-Z0-9_-]+)\}/', '<span class="text-amber-500">$0</span>', $route['uri']) !!}
                                                    </span>
                                                    @if($route['name'])
                                                        <span class="text-[8px] text-slate-600 uppercase tracking-widest mt-0.5">Name: {{ $route['name'] }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-5 py-2.5 align-top">
                                                <div class="flex flex-col">
                                                    <span class="text-slate-300 font-bold">{{ $shortHandler }}</span>
                                                    @if(str_contains($route['handler'], '\\'))
                                                        <span class="text-[8px] text-slate-700 truncate block max-w-[200px]" title="{{ $route['handler'] }}">{{ str_replace($shortHandler, '', $route['handler']) }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-5 py-2.5 text-right align-top">
                                                <div class="flex flex-col items-end gap-1">
                                                    @if($route['middleware'] && $route['middleware'] !== '-')
                                                        <div class="flex flex-wrap justify-end gap-1">
                                                            @foreach(explode(', ', $route['middleware']) as $mw)
                                                                <span class="text-[8px] bg-slate-800 text-slate-500 px-1 rounded">{{ $mw }}</span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-[9px] text-slate-800">No Middleware</span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="text-center mt-12 pb-16">
        <a href="{{ url('_system') }}" class="text-[10px] font-black text-slate-600 hover:text-purple-400 transition-all uppercase tracking-widest flex items-center justify-center gap-2">
            <i data-lucide="chevrons-left" class="w-3.5 h-3.5"></i>
            Back to Dashboard
        </a>
    </div>

    <script>
        document.querySelectorAll('.accordion-header').forEach(header => {
            header.addEventListener('click', () => {
                const item = header.parentElement;
                item.classList.toggle('is-active');
            });
        });

        function toggleAll(expand) {
            document.querySelectorAll('.accordion-item').forEach(item => {
                expand ? item.classList.add('is-active') : item.classList.remove('is-active');
            });
        }

        const sInput = document.getElementById('routeSearch');
        sInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.accordion-item');
            
            if (term.length > 0) toggleAll(true);
            else items.forEach((it, idx) => idx === 0 ? it.classList.add('is-active') : it.classList.remove('is-active'));

            document.querySelectorAll('.route-row').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
            });

            items.forEach(item => {
                const hasMatch = Array.from(item.querySelectorAll('.route-row')).some(r => r.style.display !== 'none');
                item.style.display = hasMatch ? '' : 'none';
            });
        });
    </script>
    
    <style>
        .accordion-content { transition: max-height 0.3s ease-out; }
        .is-active { border-color: #7c3aed !important; background: #0f172a !important; }
        .is-active .accordion-header { background: rgba(124, 58, 237, 0.05); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
@endsection
