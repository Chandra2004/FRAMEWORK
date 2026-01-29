<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $class }} - {{ $error_code ?? 500 }} | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

        :root {
            --bg-main: #0d1117;
            --bg-card: #161b22;
            --border: rgba(255, 255, 255, 0.1);
            --error: #f85149;
            --warning: #d29922;
            --text-main: #c9d1d9;
            --text-muted: #8b949e;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            margin: 0;
            line-height: 1.5;
        }

        .premium-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
        }

        /* EDITOR DESIGN */
        .editor-container {
            font-family: 'JetBrains Mono', monospace;
            background-color: #0d1117;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .editor-header {
            background-color: #161b22;
            padding: 8px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .window-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .editor-content {
            padding: 12px 0;
            overflow-x: auto;
        }

        .code-line {
            display: flex;
            width: 100%;
            height: 22px;
            align-items: center;
            transition: background 0.1s;
        }

        .ln-col {
            width: 50px;
            text-align: right;
            padding-right: 16px;
            color: #484f58;
            user-select: none;
            flex-shrink: 0;
            font-size: 11px;
            border-right: 1px solid var(--border);
        }

        .code-col {
            padding-left: 16px;
            white-space: pre;
            color: #c9d1d9;
            font-size: 13px;
        }

        .error-line {
            background: rgba(187, 128, 9, 0.12) !important;
            position: relative;
        }

        .error-line::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--warning);
        }

        .glow-error {
            text-shadow: 0 0 20px rgba(248, 81, 73, 0.3);
        }

        /* STACK TRACE ENHANCEMENTS */
        .trace-item {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .trace-item:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.02);
        }

        .trace-badge {
            font-size: 9px;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .trace-badge-app {
            background: rgba(35, 134, 54, 0.15);
            color: #3fb950;
            border: 1px solid rgba(63, 185, 80, 0.2);
        }

        .trace-badge-vendor {
            background: rgba(139, 148, 158, 0.1);
            color: #8b949e;
            border: 1px solid rgba(139, 148, 158, 0.2);
        }

        .trace-args {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: #58a6ff;
        }

        .code-snippet-trace {
            margin-top: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            overflow: hidden;
            display: none;
            /* Hidden by default */
        }

        .trace-item.active .code-snippet-trace {
            display: block;
        }

        .trace-item.active {
            border-left: 2px solid #58a6ff;
        }
    </style>
</head>

<body class="p-4 md:p-8 bg-[#090c10]">
    <div class="max-w-6xl mx-auto space-y-8">
        <!-- Hero Section -->
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div
                    class="px-3 py-1 bg-[#f8514915] border border-[#f8514933] text-[#ff7b72] text-[11px] font-black rounded-full uppercase tracking-[0.1em]">
                    Runtime Error — HTTP {{ $error_code ?? 500 }}
                </div>
                <div class="h-1 w-1 bg-slate-700 rounded-full"></div>
                <div class="text-slate-500 text-xs font-medium">
                    {{ date('H:i:s') }}
                </div>
            </div>

            <h1 class="text-4xl md:text-5xl font-black text-white glow-error break-words leading-tight">
                {{ $class }}
            </h1>

            <div class="p-5 bg-[#f8514910] border-l-4 border-[#f85149] rounded-r-xl">
                <p class="text-lg md:text-xl text-[#ff7b72] font-medium leading-relaxed italic">
                    "{{ $message }}"
                </p>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Left Side: Source & Stack -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Main Code Preview -->
                <div class="editor-container">
                    <div class="editor-header">
                        <div class="flex items-center gap-4">
                            <div class="flex gap-1.5">
                                <div class="window-dot bg-[#ff5f56]"></div>
                                <div class="window-dot bg-[#ffbd2e]"></div>
                                <div class="window-dot bg-[#27c93f]"></div>
                            </div>
                            <div class="flex items-center gap-2 text-slate-400 text-xs font-bold font-mono">
                                <i data-lucide="file-code" class="w-3.5 h-3.5 text-blue-400"></i>
                                {{ basename($file) }}:{{ $line }}
                            </div>
                        </div>
                    </div>
                    <div class="editor-content">
                        @foreach ($code_snippet as $lineNum => $codeLine)
                            <div class="code-line {{ $lineNum == $line ? 'error-line' : '' }}">
                                <div class="ln-col">{{ $lineNum }}</div>
                                <div class="code-col">{!! htmlspecialchars(rtrim($codeLine)) !!}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- ENHANCED STACK TRACE -->
                <div class="space-y-4">
                    <h3
                        class="text-xs font-black uppercase tracking-widest text-slate-500 flex items-center gap-2 px-2">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                        Call Stack
                    </h3>

                    <div class="space-y-3">
                        @foreach ($trace_parsed as $index => $item)
                            <div class="premium-card p-4 trace-item cursor-pointer {{ $index === 0 ? 'active' : '' }}"
                                onclick="this.classList.toggle('active')">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-7 h-7 bg-slate-800 rounded flex items-center justify-center text-[10px] font-bold text-slate-500 shrink-0">
                                        #{{ count($trace_parsed) - $index }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-3 mb-1.5 flex-wrap">
                                            <span
                                                class="trace-badge {{ $item['is_app'] ? 'trace-badge-app' : 'trace-badge-vendor' }}">
                                                {{ $item['is_app'] ? 'Application' : 'Vendor' }}
                                            </span>
                                            <span class="text-sm font-bold text-white font-mono truncate">
                                                @if ($item['class'])
                                                    {{ $item['class'] }}{{ $item['type'] }}
                                                @endif{{ $item['function'] }}()
                                            </span>
                                        </div>

                                        <div class="text-xs text-slate-500 font-mono flex items-center gap-2 truncate">
                                            <i data-lucide="folder" class="w-3 h-3"></i>
                                            {{ $item['file'] ?: '[internal]' }}@if ($item['line'])
                                                :{{ $item['line'] }}
                                            @endif
                                        </div>

                                        @if (!empty($item['args']))
                                            <div
                                                class="mt-2 text-[11px] text-slate-600 bg-black/20 p-2 rounded border border-white/5 overflow-x-auto scrollbar-hide">
                                                <span class="text-slate-400 mr-2">Arguments:</span>
                                                <span class="trace-args">{{ implode(', ', $item['args']) }}</span>
                                            </div>
                                        @endif

                                        <!-- Code Snippet for Trace Item -->
                                        @if ($item['is_app'] && !empty($item['snippet']))
                                            <div class="code-snippet-trace editor-container mt-4 border-[#58a6ff33]">
                                                <div class="editor-content !p-0 !py-2 bg-[#0d1117]">
                                                    @foreach ($item['snippet'] as $sLn => $sCode)
                                                        <div
                                                            class="code-line {{ $sLn == $item['line'] ? 'error-line !bg-[#58a6ff1a] !before:bg-[#58a6ff]' : '' }}">
                                                            <div class="ln-col !border-white/5">{{ $sLn }}
                                                            </div>
                                                            <div class="code-col !text-xs !pl-4">{!! htmlspecialchars(rtrim($sCode)) !!}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Side: Meta Info -->
            <div class="space-y-6">
                <!-- Request Context -->
                <div class="premium-card">
                    <div class="px-4 py-3 border-b border-white/5 bg-white/5 flex items-center gap-2">
                        <i data-lucide="globe" class="w-4 h-4 text-cyan-400"></i>
                        <span class="text-xs font-bold uppercase tracking-wider">Request Context</span>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="space-y-1">
                            <label class="text-[10px] uppercase font-black text-slate-500 tracking-widest">URL</label>
                            <div class="text-xs font-mono text-blue-400 break-all bg-black/30 p-2 rounded">
                                {{ $request_info['method'] }} {{ $request_info['uri'] }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] uppercase font-black text-slate-500 tracking-widest">Client
                                IP</label>
                            <div class="text-xs font-mono text-slate-300">{{ $request_info['ip'] }}</div>
                        </div>
                        @if (!empty($request_info['query']))
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-slate-500 tracking-widest">Query
                                    Params</label>
                                <pre class="text-[10px] bg-black/20 p-2 rounded border border-white/5 overflow-x-auto text-slate-400">{{ json_encode($request_info['query'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Environment -->
                <div class="premium-card">
                    <div class="px-4 py-3 border-b border-white/5 bg-white/5 flex items-center gap-2">
                        <i data-lucide="server" class="w-4 h-4 text-purple-400"></i>
                        <span class="text-xs font-bold uppercase tracking-wider">System State</span>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500">PHP Version</span>
                            <span class="text-slate-300 font-mono">{{ $environment['php_version'] }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500">App Mode</span>
                            <span
                                class="px-2 py-0.5 bg-blue-500/10 text-blue-400 rounded-md font-bold text-[10px]">{{ $environment['app_env'] }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500">Memory Usage</span>
                            <span class="text-slate-300">{{ $environment['memory_usage'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- App Details Buttons -->
                <div class="flex flex-col gap-3">
                    <a href="{{ url('/') }}"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-white text-black font-black rounded-xl hover:bg-slate-200 transition-all text-sm uppercase tracking-tighter">
                        <i data-lucide="home" class="w-4 h-4"></i>
                        Back to Home
                    </a>
                    <button onclick="location.reload()"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-800 border border-white/10 text-white font-bold rounded-xl hover:bg-slate-700 transition-all text-sm">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        Retry Request
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="pt-12 text-center text-slate-600 space-y-2">
            <div class="text-[10px] font-black uppercase tracking-[0.3em]">The Framework Support Engine</div>
            <div class="text-xs italic">"Debugging is like being the detective in a crime movie where you are also the
                murderer."</div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
