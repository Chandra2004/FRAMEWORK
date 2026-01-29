<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Failure - {{ $class ?? 'E_WARNING' }} | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

        :root {
            --bg-main: #0d1117;
            --bg-card: #161b22;
            --border: rgba(210, 153, 34, 0.2);
            --warning: #d29922;
            --text-main: #c9d1d9;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            margin: 0;
            line-height: 1.5;
            min-height: 100vh;
        }

        .premium-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .editor-container {
            font-family: 'JetBrains Mono', monospace;
            background-color: #0d1117;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .editor-header {
            background-color: #161b22;
            padding: 10px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .window-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .editor-content {
            padding: 16px 0;
            overflow-x: auto;
            max-height: 500px;
        }

        .code-line {
            display: flex;
            width: 100%;
            height: 24px;
            align-items: center;
        }

        .ln-col {
            width: 55px;
            text-align: right;
            padding-right: 20px;
            color: #484f58;
            user-select: none;
            flex-shrink: 0;
            font-size: 12px;
            border-right: 1px solid var(--border);
        }

        .code-col {
            padding-left: 20px;
            white-space: pre;
            color: #c9d1d9;
            font-size: 14px;
        }

        .highlight-line {
            background: rgba(210, 153, 34, 0.1);
            position: relative;
        }

        .highlight-line::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--warning);
        }

        .highlight-line .ln-col {
            color: var(--warning);
            font-weight: bold;
        }

        .glow-warning {
            text-shadow: 0 0 20px rgba(210, 153, 34, 0.2);
        }

        .trace-item {
            transition: all 0.2s;
            cursor: pointer;
        }

        .trace-item:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .code-snippet-trace {
            margin-top: 12px;
            border-radius: 8px;
            overflow: hidden;
            display: none;
        }

        .trace-item.active .code-snippet-trace {
            display: block;
        }
    </style>
</head>

<body class="relative">
    <main class="relative z-10 p-4 md:p-12">
        <!-- Background Decorative Elements -->
        <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full h-full -z-10 pointer-events-none overflow-hidden">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-cyan-500/10 blur-[120px] rounded-full"></div>
            <div class="absolute bottom-[10%] right-[-10%] w-[30%] h-[30%] bg-blue-600/10 blur-[120px] rounded-full">
            </div>
        </div>

        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header Section -->
            <div class="space-y-4">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 bg-amber-500/10 border border-amber-500/20 text-amber-500 text-xs font-black rounded-md uppercase tracking-widest">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    View Rendering Warning
                </div>

                <h1 class="text-3xl md:text-5xl font-bold glow-warning uppercase tracking-tighter">
                    {{ $class ?? 'E_WARNING' }}
                </h1>

                <p class="text-xl text-slate-300 max-w-3xl leading-relaxed italic">
                    "{{ $message }}"
                </p>
            </div>

            <!-- Meta Info -->
            <div class="grid md:grid-cols-2 gap-4">
                <div class="premium-card p-5 space-y-2">
                    <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Source Template</div>
                    <div class="text-sm font-mono text-slate-300 truncate" title="{{ $file }}">
                        {{ $file }}
                    </div>
                    <div class="text-xs text-amber-500 font-bold">Line: {{ $line }}</div>
                </div>

                <div class="premium-card p-5 space-y-2 bg-gradient-to-r from-[#161b22] to-[#1c2128]">
                    <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Framework State</div>
                    <div class="flex items-center gap-4">
                        <div class="text-sm font-bold text-slate-300">Environment:
                            {{ $environment['app_env'] ?? 'local' }}</div>
                        <div class="w-px h-4 bg-slate-700"></div>
                        <div class="text-sm font-bold text-slate-300">PHP {{ PHP_VERSION }}</div>
                    </div>
                    <div class="text-xs text-slate-500 italic">Execution continues, but the layout may be compromised.
                    </div>
                </div>
            </div>

            <!-- Code Preview -->
            @if (!empty($code_snippet))
                <div class="editor-container">
                    <div class="editor-header">
                        <div class="flex items-center gap-4">
                            <div class="flex gap-1.5">
                                <div class="window-dot bg-[#ff5f56]"></div>
                                <div class="window-dot bg-[#ffbd2e]"></div>
                                <div class="window-dot bg-[#27c93f]"></div>
                            </div>
                            <div class="text-xs font-medium text-slate-500 font-mono">{{ basename($file) }}</div>
                        </div>
                        <div class="text-[10px] font-black text-slate-700 uppercase italic">Live Snapshot</div>
                    </div>
                    <div class="editor-content scrollbar-hide">
                        @foreach ($code_snippet as $lineNum => $codeLine)
                            <div class="code-line {{ $lineNum == $line ? 'highlight-line' : '' }}">
                                <div class="ln-col">{{ $lineNum }}</div>
                                <div class="code-col">{!! htmlspecialchars(rtrim($codeLine)) !!}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Stack Trace (If available) -->
            @if (!empty($trace_parsed))
                <div class="space-y-4">
                    <h3
                        class="text-xs font-black uppercase tracking-widest text-slate-500 flex items-center gap-2 px-2">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                        Call Stack
                    </h3>
                    <div class="space-y-3">
                        @foreach (array_slice($trace_parsed, 0, 5) as $index => $item)
                            <div class="premium-card p-4 trace-item {{ $index === 0 ? 'active' : '' }}"
                                onclick="this.classList.toggle('active')">
                                <div class="flex items-center justify-between mb-2">
                                    <span
                                        class="text-sm font-bold text-white font-mono truncate">{{ $item['function'] }}()</span>
                                    <span
                                        class="text-[10px] px-2 py-0.5 bg-slate-800 text-slate-500 rounded uppercase font-black">{{ $item['is_app'] ? 'App' : 'Kern' }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono truncate">
                                    {{ $item['file'] }}:{{ $item['line'] }}</div>

                                @if ($item['is_app'] && !empty($item['snippet']))
                                    <div class="code-snippet-trace editor-container mt-4 border-amber-500/20">
                                        <div class="editor-content !p-0 !py-2 bg-[#0d1117]">
                                            @foreach ($item['snippet'] as $sLn => $sCode)
                                                <div
                                                    class="code-line {{ $sLn == $item['line'] ? 'highlight-line' : '' }}">
                                                    <div class="ln-col !border-white/5">{{ $sLn }}</div>
                                                    <div class="code-col !text-xs !pl-4">{!! htmlspecialchars(rtrim($sCode)) !!}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="flex items-center justify-center gap-4 pt-4 pb-12">
                <button onclick="history.back()"
                    class="px-8 py-3 bg-slate-800 text-slate-300 font-black rounded-xl hover:bg-slate-700 transition-all flex items-center gap-2 uppercase tracking-tighter text-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Go Back
                </button>
                <a href="{{ url('/') }}"
                    class="px-8 py-3 bg-amber-500 text-black font-black rounded-xl hover:bg-amber-400 transition-all flex items-center gap-2 uppercase tracking-tighter text-sm">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    Home
                </a>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
