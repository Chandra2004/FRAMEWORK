<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Error — The Framework</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #131316;
            color: #f4f4f5;
            min-height: 100vh;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #3f3f46;
            border-radius: 3px;
        }

        .header {
            background: linear-gradient(180deg, #0f1520, #131316);
            border-bottom: 1px solid #27272a;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #8B5CF6, #A78BFA, #8B5CF6);
        }

        .header .glow {
            position: absolute;
            top: -100px;
            left: -80px;
            width: 500px;
            height: 400px;
            background: rgba(139, 92, 246, 0.06);
            filter: blur(120px);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
            padding: 28px 32px;
        }

        .view-icon {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .view-icon .icon-box {
            width: 28px;
            height: 28px;
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .view-icon span {
            color: #A78BFA;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .header h1 {
            font-size: 22px;
            font-weight: 800;
            color: #f4f4f5;
            line-height: 1.3;
            margin-bottom: 12px;
        }

        .header-meta {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .view-badge {
            background: rgba(139, 92, 246, 0.15);
            color: #A78BFA;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .meta-text {
            color: #52525b;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
        }

        .main-grid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 32px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .card {
            background: #18181c;
            border: 1px solid #27272a;
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 12px 20px;
            background: #1e1e22;
            border-bottom: 1px solid #27272a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h3 {
            font-size: 11px;
            font-weight: 700;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .card-header .icon {
            color: #A78BFA;
        }

        .code-container {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.7;
            overflow-x: auto;
            padding: 12px 0;
        }

        .code-line {
            display: flex;
            padding: 0 16px;
            border-left: 3px solid transparent;
        }

        .code-line:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .code-line.active {
            background: rgba(139, 92, 246, 0.08);
            border-left-color: #8B5CF6;
        }

        .code-line.active .line-num {
            color: #A78BFA;
            font-weight: 700;
        }

        .line-num {
            width: 3.5rem;
            text-align: right;
            padding-right: 1.25rem;
            color: #3f3f46;
            user-select: none;
            border-right: 1px solid #27272a;
            margin-right: 1.25rem;
            flex-shrink: 0;
        }

        .line-code {
            white-space: pre;
            color: #d4d4d8;
        }

        .file-path {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: #71717a;
            border-bottom: 1px solid #27272a;
            background: #1a1a1e;
        }

        .file-path .hl {
            color: #A78BFA;
            font-weight: 700;
        }

        .blade-tip {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.06), rgba(139, 92, 246, 0.02));
            border: 1px solid rgba(139, 92, 246, 0.15);
            border-radius: 12px;
            padding: 20px 24px;
        }

        .blade-tip h3 {
            color: #A78BFA;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .blade-tip ul {
            list-style: none;
            padding: 0;
        }

        .blade-tip li {
            color: #a1a1aa;
            font-size: 13px;
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
            line-height: 1.5;
        }

        .blade-tip li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: #A78BFA;
        }

        .blade-tip code {
            color: #A78BFA;
            background: #1e1e22;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .kv-section {
            padding: 16px 20px;
        }

        .kv-row {
            display: flex;
            padding: 6px 0;
            font-size: 12px;
            border-bottom: 1px solid #1e1e22;
        }

        .kv-row:last-child {
            border-bottom: none;
        }

        .kv-key {
            width: 140px;
            flex-shrink: 0;
            color: #71717a;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 500;
        }

        .kv-val {
            flex: 1;
            color: #d4d4d8;
            font-family: 'JetBrains Mono', monospace;
            word-break: break-all;
        }

        .btn-row {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #27272a;
            border: 1px solid #3f3f46;
            color: #a1a1aa;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s;
        }

        .btn:hover {
            background: #3f3f46;
            color: #f4f4f5;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="glow"></div>
        <div class="header-content">
            <div class="view-icon">
                <div class="icon-box">⚡</div>
                <span>Blade Compilation Error</span>
            </div>
            <h1>{{ $message }}</h1>
            <div class="header-meta">
                <span class="view-badge">VIEW ERROR</span>
                <span class="meta-text">{{ basename($file ?? 'unknown.blade.php') }}</span>
                <span style="color:#3f3f46">•</span>
                <span class="meta-text">{{ $request_info['uri'] ?? '/' }}</span>
            </div>
        </div>
    </div>

    <div class="main-grid">
        <!-- Blade Template Source -->
        <div class="card">
            <div class="card-header">
                <svg class="icon" width="14" height="14" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                </svg>
                <h3>Blade Template</h3>
            </div>
            <div class="file-path">
                <span>{{ $file ?? 'Unknown' }}</span>
                <span style="color:#3f3f46">:</span>
                <span class="hl">{{ $line ?? '?' }}</span>
            </div>
            <div class="code-container">
                @if (!empty($code_snippet))
                    @foreach ($code_snippet as $num => $code)
                        <div class="code-line {{ $num == ($line ?? 0) ? 'active' : '' }}">
                            <div class="line-num">{{ $num }}</div>
                            <div class="line-code">{{ $code }}</div>
                        </div>
                    @endforeach
                @else
                    <div style="padding:24px;text-align:center;color:#52525b;font-size:13px;">Cannot read source
                        template</div>
                @endif
            </div>
        </div>

        <!-- Blade Tips -->
        <div class="blade-tip">
            <h3>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                    </path>
                </svg>
                Blade Troubleshooting
            </h3>
            <ul>
                <li>Jika <strong>"Undefined variable"</strong> — pastikan variabel dikirim dari Controller: <code>return
                        view('page', ['var' => $value])</code></li>
                <li>Jika <strong>"Class not found"</strong> — cek namespace dan pastikan <code>use</code> statement
                    benar</li>
                <li>Jika <strong>"Call to undefined function"</strong> — pastikan helper terdaftar di
                    <code>helpers.php</code></li>
                <li>Clear view cache: <code>php artisan view:clear</code></li>
                <li>Cek syntax Blade directive: <code>@@if</code>, <code>@@foreach</code>, <code>@@php</code> harus punya
                    closing tag</li>
            </ul>
        </div>

        <!-- Stack Trace (if exists) -->
        @if (!empty($trace_parsed))
            <div class="card">
                <div class="card-header">
                    <h3>Stack Trace ({{ count($trace_parsed) }} frames)</h3>
                </div>
                <div style="max-height:300px;overflow-y:auto;">
                    @foreach ($trace_parsed as $frame)
                        <div
                            style="padding:8px 20px;border-bottom:1px solid #1e1e22;font-family:'JetBrains Mono',monospace;font-size:12px;display:flex;align-items:center;gap:8px;">
                            <span
                                style="background:{{ $frame['is_app'] ? 'rgba(139,92,246,0.15)' : '#1e1e22' }};color:{{ $frame['is_app'] ? '#A78BFA' : '#52525b' }};padding:2px 6px;border-radius:4px;font-size:9px;font-weight:700;text-transform:uppercase;white-space:nowrap;">{{ $frame['is_app'] ? 'App' : 'Vendor' }}</span>
                            <span
                                style="color:#a1a1aa;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                @if ($frame['class'])<span
                                        style="color:#52525b">{{ class_basename($frame['class']) }}{{ $frame['type'] }}</span>
                                @endif
                                <span style="color:#d4d4d8">{{ $frame['function'] }}</span>
                            </span>
                            <span style="color:#52525b;flex-shrink:0">{{ basename($frame['file']) }}:<span
                                    style="color:#A78BFA">{{ $frame['line'] }}</span></span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Info -->
        <div class="info-grid">
            <div class="card">
                <div class="card-header">
                    <h3>Request</h3>
                </div>
                <div class="kv-section">
                    <div class="kv-row">
                        <div class="kv-key">Method</div>
                        <div class="kv-val">{{ $request_info['method'] ?? 'GET' }}</div>
                    </div>
                    <div class="kv-row">
                        <div class="kv-key">URL</div>
                        <div class="kv-val">{{ $request_info['uri'] ?? '/' }}</div>
                    </div>
                    <div class="kv-row">
                        <div class="kv-key">Client IP</div>
                        <div class="kv-val">{{ $request_info['ip'] ?? '127.0.0.1' }}</div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Environment</h3>
                </div>
                <div class="kv-section">
                    <div class="kv-row">
                        <div class="kv-key">PHP</div>
                        <div class="kv-val" style="color:#3B82F6;font-weight:700">
                            {{ $environment['php_version'] ?? PHP_VERSION }}</div>
                    </div>
                    <div class="kv-row">
                        <div class="kv-key">App Env</div>
                        <div class="kv-val"><span
                                class="view-badge">{{ strtoupper($environment['app_env'] ?? 'local') }}</span></div>
                    </div>
                    <div class="kv-row">
                        <div class="kv-key">Memory</div>
                        <div class="kv-val">{{ $environment['memory_usage'] ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="btn-row">
            <a href="{{ url('/') }}" class="btn">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                    </path>
                </svg>
                Go Home
            </a>
            <a href="javascript:location.reload()" class="btn">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
                Retry
            </a>
        </div>
    </div>
</body>

</html>
