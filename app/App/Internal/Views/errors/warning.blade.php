<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $class ?? 'Warning' }} — The Framework</title>
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
            background: linear-gradient(180deg, #18181c, #131316);
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
            background: linear-gradient(90deg, #F59E0B, #FBBF24, #F59E0B);
        }

        .header .glow {
            position: absolute;
            top: -120px;
            left: -60px;
            width: 500px;
            height: 500px;
            background: rgba(245, 158, 11, 0.06);
            filter: blur(150px);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
            padding: 24px 32px;
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
        }

        .header-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #F59E0B;
            animation: pulse 2s infinite;
        }

        .header-badge span {
            color: #71717a;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.05em;
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

        .method-badge {
            background: rgba(245, 158, 11, 0.15);
            color: #FBBF24;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .meta-text {
            color: #52525b;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
        }

        .meta-dot {
            color: #3f3f46;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr;
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 32px;
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
            justify-content: between;
        }

        .card-header h3 {
            font-size: 11px;
            font-weight: 700;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .card-body {
            padding: 0;
        }

        .code-container {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.7;
            overflow-x: auto;
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
            background: rgba(245, 158, 11, 0.08);
            border-left-color: #F59E0B;
        }

        .code-line.active .line-num {
            color: #F59E0B;
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

        .file-path .line-highlight {
            color: #F59E0B;
            font-weight: 700;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .kv-section {
            padding: 16px 20px;
        }

        .kv-section h4 {
            font-size: 11px;
            font-weight: 700;
            color: #52525b;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #27272a;
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
            margin-top: 24px;
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

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .main-grid {
                padding: 16px;
            }

            .header-content {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="glow"></div>
        <div class="header-content">
            <div class="header-badge">
                <div class="dot"></div>
                <span>{{ $class ?? 'PHP Warning' }}</span>
            </div>
            <h1>{{ $message }}</h1>
            <div class="header-meta">
                <span class="method-badge">{{ $error_code_text ?? 'WARNING' }}</span>
                <span class="meta-text">{{ $request_info['uri'] ?? '/' }}</span>
                <span class="meta-dot">•</span>
                <span class="meta-text">PHP {{ $environment['php_version'] ?? PHP_VERSION }}</span>
            </div>
        </div>
    </div>

    <div class="main-grid">
        <!-- Source Code -->
        <div class="card">
            <div class="card-header">
                <h3>Source Code</h3>
            </div>
            <div class="file-path">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    style="flex-shrink:0">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                </svg>
                <span>{{ $file }}</span>
                <span class="meta-dot">:</span>
                <span class="line-highlight">{{ $line }}</span>
            </div>
            <div class="card-body">
                <div class="code-container" style="padding: 12px 0;">
                    @if (!empty($code_snippet))
                        @foreach ($code_snippet as $num => $code)
                            <div class="code-line {{ $num == $line ? 'active' : '' }}">
                                <div class="line-num">{{ $num }}</div>
                                <div class="line-code">{{ $code }}</div>
                            </div>
                        @endforeach
                    @else
                        <div style="padding: 24px; text-align: center; color: #52525b; font-size: 13px;">No source code
                            available</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="info-grid">
            <div class="card">
                <div class="card-header">
                    <h3>Request Info</h3>
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
                                class="method-badge">{{ strtoupper($environment['app_env'] ?? 'local') }}</span></div>
                    </div>
                    <div class="kv-row">
                        <div class="kv-key">Memory</div>
                        <div class="kv-val">{{ $environment['memory_usage'] ?? 'N/A' }}</div>
                    </div>
                    <div class="kv-row">
                        <div class="kv-key">Framework</div>
                        <div class="kv-val" style="color:#F59E0B;font-weight:600">v5.0.1</div>
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
