<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatal Error - {{ $error_code ?? 500 }} | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

        :root {
            --bg-main: #0a0000;
            --bg-card: #1a0505;
            --border: rgba(248, 81, 73, 0.15);
            --error: #f85149;
            --text-main: #f0f6fc;
            --text-muted: #8b949e;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            margin: 0;
            line-height: 1.5;
            background-image: radial-gradient(circle at 50% 0%, #3d1414 0%, #0a0000 70%);
        }

        .premium-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
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
            color: var(--text-main);
            font-size: 13px;
        }

        .error-line {
            background: rgba(248, 81, 73, 0.15) !important;
            position: relative;
        }

        .error-line::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--error);
        }

        .glow-error {
            text-shadow: 0 0 30px rgba(248, 81, 73, 0.5);
        }

        .trace-item {
            transition: all 0.2s;
            cursor: pointer;
        }

        .trace-item:hover {
            background: rgba(248, 81, 73, 0.03);
        }

        .trace-badge {
            font-size: 9px;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .trace-badge-app {
            background: rgba(248, 81, 73, 0.15);
            color: #f85149;
            border: 1px solid rgba(248, 81, 73, 0.3);
        }

        .trace-badge-vendor {
            background: rgba(139, 148, 158, 0.1);
            color: #8b949e;
            border: 1px solid rgba(139, 148, 158, 0.2);
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

        .trace-item.active {
            border-left: 2px solid #f85149;
        }
    </style>
</head>

<body class="p-4 md:p-8 min-h-screen">
    <div class="max-w-6xl mx-auto space-y-8">
        <!-- Fatal Hero -->
        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <div
                    class="px-4 py-1.5 bg-red-500/10 border border-red-500/30 text-red-500 text-[10px] font-black rounded-full uppercase tracking-[0.2em] animate-pulse">
                    CRITICAL SYSTEM FAILURE
                </div>
                <div class="text-slate-500 text-xs font-mono">
                    {{ date('H:i:s') }}
                </div>
            </div>

            <h1 class="text-5xl md:text-7xl font-black text-white glow-error uppercase italic tracking-tighter">
                {{ $error_code_text ?? 'FATAL_ERROR' }}
            </h1>

            <div class="p-6 bg-red-500/5 border-l-4 border-red-500 rounded-r-xl">
                <p class="text-xl md:text-2xl text-red-100/70 font-medium leading-relaxed italic">
                    "{{ $message }}"
                </p>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <!-- Code Preview -->
                @if (!empty($code_snippet))
                    <div class="editor-container shadow-2xl shadow-red-900/20">
                        <div class="editor-header">
                            <div class="flex items-center gap-4">
                                <div class="window-dot bg-[#ff5f56]"></div>
                                <div class="window-dot bg-[#ffbd2e]"></div>
                                <div class="window-dot bg-[#27c93f]"></div>
                                <div class="text-slate-400 text-xs font-bold font-mono ml-2">
                                    {{ basename($file) }}:{{ $line }}
                                </div>
                            </div>
                        </div>
                        <div class="editor-content scrollbar-hide">
                            @foreach ($code_snippet as $lineNum => $codeLine)
                                <div class="code-line {{ $lineNum == $line ? 'error-line' : '' }}">
                                    <div class="ln-col">{{ $lineNum }}</div>
                                    <div class="code-col font-medium">{!! htmlspecialchars(rtrim($codeLine)) !!}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Interactive Stack Trace -->
                @if (!empty($trace_parsed))
                    <div class="space-y-4">
                        <h3
                            class="text-xs font-black uppercase tracking-widest text-red-500/50 flex items-center gap-2 px-2">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                            Crash Analysis (Stack Trace)
                        </h3>
                        <div class="space-y-3">
                            @foreach ($trace_parsed as $index => $item)
                                <div class="premium-card p-4 trace-item {{ $index === 0 ? 'active' : '' }}"
                                    onclick="this.classList.toggle('active')">
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="w-7 h-7 bg-red-500/10 rounded flex items-center justify-center text-[10px] font-bold text-red-500/50">
                                            #{{ count($trace_parsed) - $index }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                                <span
                                                    class="trace-badge {{ $item['is_app'] ? 'trace-badge-app' : 'trace-badge-vendor' }}">
                                                    {{ $item['is_app'] ? 'App' : 'Internal' }}
                                                </span>
                                                <span class="text-sm font-bold text-white font-mono truncate">
                                                    @if ($item['class'])
                                                        {{ $item['class'] }}{{ $item['type'] }}
                                                    @endif{{ $item['function'] }}()
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-500 font-mono truncate">
                                                {{ $item['file'] ?: '[internal]' }}@if ($item['line'])
                                                    :{{ $item['line'] }}
                                                @endif
                                            </div>
                                            @if ($item['is_app'] && !empty($item['snippet']))
                                                <div class="code-snippet-trace editor-container mt-4 border-red-500/20">
                                                    <div class="editor-content !p-0 !py-2 bg-[#0d1117]">
                                                        @foreach ($item['snippet'] as $sLn => $sCode)
                                                            <div
                                                                class="code-line {{ $sLn == $item['line'] ? 'error-line' : '' }}">
                                                                <div class="ln-col !border-white/5">{{ $sLn }}
                                                                </div>
                                                                <div class="code-col !text-xs !pl-4">
                                                                    {!! htmlspecialchars(rtrim($sCode)) !!}</div>
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
                @endif
            </div>

            <!-- Sidebars -->
            <div class="space-y-6">
                <!-- Location Info -->
                <div class="premium-card p-6 bg-gradient-to-br from-[#1a0505] to-[#2d0a0a]">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center">
                            <i data-lucide="skull" class="w-6 h-6 text-red-500"></i>
                        </div>
                        <div>
                            <div class="text-xs font-black text-red-500 uppercase">Emergency Boot</div>
                            <div class="text-white font-bold">Stalled Operations</div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <a href="{{ url('/') }}"
                        class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-white text-black font-black rounded-xl hover:bg-slate-200 transition-all uppercase tracking-tighter text-sm">
                        <i data-lucide="home" class="w-4 h-4"></i>
                        Emergency Exit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
