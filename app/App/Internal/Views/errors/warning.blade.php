<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ url('/assets/ico/favicon-debug.ico') }}">
    <title>{{ $severity_name ?? 'Warning' }} | The Framework</title>
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
            background-image: linear-gradient(180deg, rgba(210, 153, 34, 0.05) 0%, rgba(13, 17, 23, 0) 500px);
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
            max-height: 400px;
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
    </style>
</head>

<body class="p-6 md:p-12">
    <div class="max-w-5xl mx-auto space-y-8">
        <!-- Header Section -->
        <div class="space-y-4">
            <div
                class="inline-flex items-center gap-2 px-3 py-1 bg-amber-500/10 border border-amber-500/20 text-amber-500 text-xs font-black rounded-md uppercase tracking-widest">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                Runtime Warning
            </div>

            <h1 class="text-3xl md:text-5xl font-bold glow-warning">
                {{ $severity_name ?? 'E_WARNING' }}
            </h1>

            <p class="text-lg text-slate-400 max-w-3xl leading-relaxed">
                {{ $message }}
            </p>
        </div>

        <!-- Meta Info -->
        <div class="grid md:grid-cols-2 gap-4">
            <div class="premium-card p-5 space-y-2">
                <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Source Context</div>
                <div class="text-sm font-mono text-slate-300 truncate" title="{{ $file }}">
                    {{ $file }}
                </div>
                <div class="text-xs text-amber-500 font-bold">Line: {{ $line }}</div>
            </div>

            <div class="premium-card p-5 space-y-2 bg-gradient-to-r from-[#161b22] to-[#1c2128]">
                <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Framework State</div>
                <div class="flex items-center gap-4">
                    <div class="text-sm font-bold text-slate-300">Severity: {{ $severity ?? 0 }}</div>
                    <div class="w-px h-4 bg-slate-700"></div>
                    <div class="text-sm font-bold text-slate-300">PHP {{ PHP_VERSION }}</div>
                </div>
                <div class="text-xs text-slate-500 italic">Execution will continue after this warning.</div>
            </div>
        </div>

        <!-- Code Preview -->
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
                <div class="text-[10px] font-black text-slate-700 uppercase">Warning Snapshot</div>
            </div>
            <div class="editor-content">
                @php
                    if (empty($code_snippet)) {
                        $lines = @file($file);
                        $start = max(0, $line - 5);
                        $end = min(count($lines), $line + 4);
                        $code_snippet = [];
                        for ($i = $start; $i < $end; $i++) {
                            $code_snippet[$i + 1] = $lines[$i];
                        }
                    }
                @endphp
                @foreach ($code_snippet as $lineNum => $codeLine)
                    <div class="code-line {{ $lineNum == $line ? 'highlight-line' : '' }}">
                        <div class="ln-col">{{ $lineNum }}</div>
                        <div class="code-col">{!! htmlspecialchars(rtrim($codeLine)) !!}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Info Request -->
        @if (!empty($request_info))
            <div class="premium-card overflow-hidden">
                <div class="px-5 py-3 border-b border-white/5 bg-black/20 text-xs font-black uppercase text-slate-500">
                    Request Trace</div>
                <div class="p-5 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs font-mono">
                    <div>
                        <span class="block text-slate-600 mb-1">Method</span>
                        <span class="text-blue-400">{{ $request_info['method'] }}</span>
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <span class="block text-slate-600 mb-1">Path</span>
                        <span class="text-slate-300 truncate block">{{ $request_info['uri'] }}</span>
                    </div>
                    <div>
                        <span class="block text-slate-600 mb-1">Remote IP</span>
                        <span class="text-slate-300">{{ $request_info['ip'] }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex items-center justify-center gap-4 pt-4">
            <button onclick="history.back()"
                class="px-6 py-2 bg-slate-800 text-slate-300 font-bold rounded hover:bg-slate-700 transition flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Go Back
            </button>
            <a href="{{ url('/') }}"
                class="px-6 py-2 bg-amber-500 text-black font-bold rounded hover:bg-amber-400 transition flex items-center gap-2">
                <i data-lucide="home" class="w-4 h-4"></i>
                Home
            </a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
