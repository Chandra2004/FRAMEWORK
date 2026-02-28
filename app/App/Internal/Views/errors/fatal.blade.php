<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatal Error — The Framework</title>
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
            background: linear-gradient(180deg, #1a0a0a, #131316);
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
            height: 3px;
            background: linear-gradient(90deg, #DC2626, #EF4444, #DC2626);
            animation: scanline 3s ease-in-out infinite;
        }

        .header .glow {
            position: absolute;
            top: -100px;
            left: -60px;
            width: 500px;
            height: 400px;
            background: rgba(220, 38, 38, 0.08);
            filter: blur(120px);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
            padding: 28px 32px;
        }

        .fatal-icon {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .fatal-icon .skull {
            width: 28px;
            height: 28px;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .fatal-icon span {
            color: #EF4444;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .header h1 {
            font-size: 24px;
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

        .fatal-badge {
            background: rgba(220, 38, 38, 0.15);
            color: #EF4444;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid rgba(220, 38, 38, 0.25);
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
            color: #EF4444;
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
            background: rgba(220, 38, 38, 0.08);
            border-left-color: #EF4444;
        }

        .code-line.active .line-num {
            color: #EF4444;
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
            color: #EF4444;
            font-weight: 700;
        }

        .hint-box {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.06), rgba(220, 38, 38, 0.02));
            border: 1px solid rgba(220, 38, 38, 0.15);
            border-radius: 12px;
            padding: 20px 24px;
        }

        .hint-box h3 {
            color: #EF4444;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hint-box ul {
            list-style: none;
            padding: 0;
        }

        .hint-box li {
            color: #a1a1aa;
            font-size: 13px;
            padding: 4px 0;
            padding-left: 20px;
            position: relative;
        }

        .hint-box li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: #EF4444;
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

        @keyframes scanline {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: 0.5
            }
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .main-grid {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="glow"></div>
        <div class="header-content">
            <div class="fatal-icon">
                <div class="skull">💀</div>
                <span>{{ $class ?? 'FATAL ERROR' }}</span>
            </div>
            <h1>{{ $message }}</h1>
            <div class="header-meta">
                <span class="fatal-badge">Fatal Error</span>
                <span class="meta-text">{{ $request_info['uri'] ?? '/' }}</span>
                <span style="color:#3f3f46">•</span>
                <span class="meta-text">PHP {{ $environment['php_version'] ?? PHP_VERSION }}</span>
                <span style="color:#3f3f46">•</span>
                <span class="meta-text">{{ $environment['memory_usage'] ?? '' }}</span>
            </div>
        </div>
    </div>

    <div class="main-grid">
        <!-- Source Code -->
        <div class="card">
            <div class="card-header">
                <svg class="icon" width="14" height="14" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                </svg>
                <h3>Error Location</h3>
            </div>
            <div class="file-path">
                <span>{{ $file }}</span>
                <span style="color:#3f3f46">:</span>
                <span class="hl">{{ $line }}</span>
            </div>
            <div class="code-container">
                @if (!empty($code_snippet))
                    @foreach ($code_snippet as $num => $code)
                        <div class="code-line {{ $num == $line ? 'active' : '' }}">
                            <div class="line-num">{{ $num }}</div>
                            <div class="line-code">{{ $code }}</div>
                        </div>
                    @endforeach
                @else
                    <div style="padding:24px;text-align:center;color:#52525b;font-size:13px;">No source code available
                    </div>
                @endif
            </div>
        </div>

        <!-- Troubleshooting Hints -->
        <div class="hint-box">
            <h3>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                    </path>
                </svg>
                Possible Solutions
            </h3>
            <ul>
                <li>Periksa apakah ada <strong>syntax error</strong> di file yang disebutkan</li>
                <li>Pastikan semua <strong>class dan namespace</strong> sudah benar</li>
                <li>Jalankan <code
                        style="color:#EF4444;background:#1e1e22;padding:1px 6px;border-radius:4px;font-size:12px">composer
                        dump-autoload</code> untuk refresh autoloader</li>
                <li>Cek apakah <strong>memory limit</strong> PHP cukup di php.ini</li>
                <li>Pastikan semua <strong>dependency</strong> terinstall: <code
                        style="color:#EF4444;background:#1e1e22;padding:1px 6px;border-radius:4px;font-size:12px">composer
                        install</code></li>
            </ul>
        </div>

        <!-- Details -->
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
                    <div class="kv-row">
                        <div class="kv-key">User Agent</div>
                        <div class="kv-val">{{ $request_info['user_agent'] ?? 'Unknown' }}</div>
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
                                class="fatal-badge">{{ strtoupper($environment['app_env'] ?? 'local') }}</span></div>
                    </div>
                    <div class="kv-row">
                        <div class="kv-key">Memory</div>
                        <div class="kv-val">{{ $environment['memory_usage'] ?? 'N/A' }}</div>
                    </div>
                    <div class="kv-row">
                        <div class="kv-key">Peak</div>
                        <div class="kv-val">{{ $environment['memory_peak'] ?? 'N/A' }}</div>
                    </div>
                    <div class="kv-row">
                        <div class="kv-key">OS</div>
                        <div class="kv-val">{{ $environment['os'] ?? PHP_OS }}</div>
                    </div>
                    <div class="kv-row">
                        <div class="kv-key">Time</div>
                        <div class="kv-val">{{ $environment['timestamp'] ?? date('Y-m-d H:i:s') }}</div>
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
