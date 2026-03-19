<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $class }} — The Framework</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] },
                    colors: {
                        dark: { 50:'#3f3f46', 100:'#2a2a2e', 200:'#242428', 300:'#1e1e22', 400:'#18181c', 500:'#131316', 600:'#0b0b0e', 700:'#000' },
                        accent: { DEFAULT:'#F43F5E', light:'#FB7185', dark:'#BE123C', glow:'rgba(244,63,94,0.12)' },
                        success: '#22C55E', info: '#3B82F6', warn: '#F59E0B'
                    }
                }
            }
        }
    </script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #131316; color: #f4f4f5; margin: 0; overflow: hidden; height: 100vh; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }

        .code-container { font-family: 'JetBrains Mono', monospace; font-size: 13px; line-height: 1.7; }
        .code-line { display: flex; padding: 0 1rem; cursor: text; transition: background 0.1s; border-left: 3px solid transparent; }
        .code-line:hover { background: rgba(255,255,255,0.02); }
        .code-line.active { background: rgba(244,63,94,0.1); border-left-color: #F43F5E; }
        .code-line.active .line-num { color: #F43F5E; font-weight: 700; }
        .line-num { width: 4rem; text-align: right; padding-right: 1.25rem; color: #52525b; user-select: none; border-right: 1px solid #27272a; margin-right: 1.25rem; flex-shrink: 0; }
        .line-code { white-space: pre; color: #d4d4d8; }

        .frame-item { padding: 0.75rem 1.25rem; cursor: pointer; border-bottom: 1px solid #1e1e22; border-left: 3px solid transparent; transition: all 0.15s; }
        .frame-item:hover { background: #1e1e22; }
        .frame-item.active { background: #1e1e22; border-left-color: #F43F5E; }
        .frame-item.vendor { opacity: 0.5; }
        .frame-item.vendor:hover { opacity: 0.8; }
        .frame-item.vendor.active { opacity: 1; }

        .tab-btn { padding: 0.75rem 1.25rem; color: #71717a; font-size: 12px; font-weight: 600; border-bottom: 2px solid transparent; transition: all 0.15s; background: transparent; cursor: pointer; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
        .tab-btn:hover { color: #d4d4d8; }
        .tab-btn.active { color: #F43F5E; border-bottom-color: #F43F5E; }

        .kv-row { display: flex; border-bottom: 1px solid #1e1e22; padding: 0.625rem 0; font-size: 13px; }
        .kv-row:last-child { border-bottom: none; }
        .kv-key { width: 200px; flex-shrink: 0; color: #a1a1aa; font-weight: 500; font-family: 'JetBrains Mono', monospace; font-size: 12px; }
        .kv-val { flex: 1; color: #d4d4d8; font-family: 'JetBrains Mono', monospace; font-size: 12px; word-break: break-all; }

        .badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-app { background: rgba(244,63,94,0.15); color: #FB7185; border: 1px solid rgba(244,63,94,0.2); }
        .badge-vendor { background: #1e1e22; color: #71717a; border: 1px solid #27272a; }

        .copy-btn { background: #27272a; border: 1px solid #3f3f46; color: #a1a1aa; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 600; transition: all 0.15s; display: inline-flex; align-items: center; gap: 4px; }
        .copy-btn:hover { background: #3f3f46; color: #f4f4f5; }
        .copy-btn.copied { background: rgba(34,197,94,0.15); border-color: rgba(34,197,94,0.3); color: #22C55E; }

        .prev-chain { background: #1e1e22; border: 1px solid #27272a; border-radius: 8px; padding: 12px 16px; margin-bottom: 8px; }
        .toggle-vendor { cursor: pointer; padding: 8px 16px; background: #1e1e22; border: 1px solid #27272a; font-size: 11px; color: #71717a; border-radius: 6px; display: flex; align-items: center; gap: 6px; margin: 8px 12px; }
        .toggle-vendor:hover { background: #27272a; color: #a1a1aa; }
    </style>
</head>
<body class="flex flex-col h-screen antialiased">

    <!-- ═══ HEADER ═══ -->
    <header class="flex-shrink-0 relative overflow-hidden" style="background: linear-gradient(180deg, #18181c 0%, #131316 100%); border-bottom: 1px solid #27272a;">
        <div class="absolute top-0 left-0 w-full h-[2px]" style="background: linear-gradient(90deg, #F43F5E 0%, #FB7185 50%, #F43F5E 100%);"></div>
        <div class="absolute top-[-120px] left-[-60px] w-[500px] h-[500px] bg-accent/5 blur-[150px] rounded-full pointer-events-none"></div>

        <div class="flex items-start justify-between px-8 py-6 relative z-10">
            <div class="flex-1 min-w-0 pr-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                    <span class="text-zinc-500 text-xs font-mono tracking-wider">{{ $class }}</span>
                </div>
                <h1 class="text-2xl font-black text-zinc-100 leading-tight tracking-tight mb-3" id="error-message">{{ $message }}</h1>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="badge badge-app">{{ $request_info['method'] ?? 'GET' }}</span>
                    <span class="text-zinc-500 font-mono text-xs">{{ $request_info['uri'] ?? '/' }}</span>
                    <span class="text-zinc-700 mx-1">•</span>
                    <span class="text-zinc-600 font-mono text-xs">PHP {{ $environment['php_version'] ?? PHP_VERSION }}</span>
                    <span class="text-zinc-700 mx-1">•</span>
                    <span class="text-zinc-600 font-mono text-xs">{{ $environment['memory_usage'] ?? '' }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <button class="copy-btn" onclick="copyError(this)" title="Copy error message">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                    Copy
                </button>
                <a href="{{ url('/') }}" class="copy-btn" title="Go Home">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Home
                </a>
            </div>
        </div>

        @if(!empty($previous_chain))
        <div class="px-8 pb-4 relative z-10">
            <div class="text-[11px] text-zinc-500 font-semibold uppercase tracking-wider mb-2">Caused by</div>
            @foreach($previous_chain as $prev)
            <div class="prev-chain">
                <span class="text-accent text-xs font-mono font-bold">{{ $prev['class'] }}</span>
                <span class="text-zinc-400 text-xs ml-2">{{ $prev['message'] }}</span>
                <span class="text-zinc-600 text-xs ml-2 font-mono">{{ basename($prev['file']) }}:{{ $prev['line'] }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </header>

    <!-- ═══ MAIN LAYOUT ═══ -->
    <div class="flex flex-1 overflow-hidden">

        <!-- ─── Sidebar: Stack Trace ─── -->
        <aside class="w-[420px] bg-dark-400 border-r border-dark-50/20 flex flex-col shrink-0">
            <div class="px-4 py-3 bg-dark-500 border-b border-dark-50/20 flex items-center justify-between shrink-0">
                <h3 class="text-[11px] font-bold text-zinc-500 uppercase tracking-widest">Stack Trace</h3>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] bg-dark-100 text-zinc-500 px-2 py-0.5 rounded font-bold">{{ count($trace_parsed) }} FRAMES</span>
                </div>
            </div>

            <div class="toggle-vendor" onclick="toggleVendor()">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                <span id="vendor-toggle-text">Hide vendor frames</span>
            </div>

            <div class="overflow-y-auto flex-1 pb-10" id="frames-list">
                @foreach($trace_parsed as $index => $frame)
                <div class="frame-item {{ $index === 0 ? 'active' : '' }} {{ !$frame['is_app'] ? 'vendor' : '' }}" onclick="selectFrame({{ $index }}, this)" data-is-vendor="{{ !$frame['is_app'] ? '1' : '0' }}">
                    <div class="flex justify-between items-start mb-1">
                        <div class="font-mono text-[12px] text-zinc-300 truncate pr-2 font-medium">
                            @if($frame['class'])
                                <span class="text-zinc-500">{{ class_basename($frame['class']) }}{{ $frame['type'] }}</span><span class="text-zinc-200 font-semibold">{{ $frame['function'] }}</span>
                            @else
                                <span class="text-zinc-200 font-semibold">{{ $frame['function'] }}</span>
                            @endif
                        </div>
                        @if($frame['is_app'])
                            <span class="badge badge-app shrink-0">App</span>
                        @else
                            <span class="badge badge-vendor shrink-0">Vendor</span>
                        @endif
                    </div>
                    <div class="text-[11px] text-zinc-600 font-mono truncate flex items-center gap-1">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        {{ basename($frame['file']) }}<span class="text-accent">:{{ $frame['line'] ?? '?' }}</span>
                    </div>
                    @if(!empty($frame['args']))
                    <div class="text-[10px] text-zinc-700 font-mono mt-1 truncate">({{ implode(', ', $frame['args']) }})</div>
                    @endif
                </div>
                @endforeach
            </div>
        </aside>

        <!-- ─── Main Content ─── -->
        <main class="flex-1 flex flex-col overflow-hidden bg-dark-300">

            <!-- Code Snippet -->
            <div class="h-[55%] flex flex-col border-b border-dark-50/20" style="background: #1a1a1f;">
                <div class="px-5 py-2.5 border-b border-dark-50/20 flex items-center bg-dark-400 shrink-0">
                    <svg class="w-4 h-4 text-zinc-600 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                    <span class="text-[12px] font-mono text-zinc-500 truncate" id="current-file-path">{{ $file }}</span>
                    <span class="text-zinc-700 mx-1">:</span>
                    <span class="text-[12px] font-mono text-accent font-bold" id="current-file-line">{{ $line }}</span>
                </div>
                <div class="flex-1 overflow-auto py-3 code-container" id="code-snippet-body"></div>
            </div>

            <!-- Detail Tabs -->
            <div class="flex-1 flex flex-col overflow-hidden bg-dark-400">
                <div class="flex px-1 bg-dark-500 border-b border-dark-50/20 shrink-0 overflow-x-auto">
                    <button class="tab-btn active" onclick="switchTab('request', this)">Request</button>
                    <button class="tab-btn" onclick="switchTab('headers', this)">Headers</button>
                    <button class="tab-btn" onclick="switchTab('body', this)">Body</button>
                    <button class="tab-btn" onclick="switchTab('session', this)">Session</button>
                    <button class="tab-btn" onclick="switchTab('cookies', this)">Cookies</button>
                    <button class="tab-btn" onclick="switchTab('env', this)">Environment</button>
                    <button class="tab-btn" onclick="switchTab('context', this)">Context</button>
                </div>

                <div class="flex-1 overflow-y-auto p-6" id="tab-container">
                    <!-- Request -->
                    <div id="tab-request" class="tab-pane">
                        <div class="kv-row"><div class="kv-key">URL</div><div class="kv-val"><span class="badge badge-app mr-2">{{ $request_info['method'] ?? 'GET' }}</span>{{ $request_info['uri'] ?? '/' }}</div></div>
                        <div class="kv-row"><div class="kv-key">Client IP</div><div class="kv-val">{{ $request_info['ip'] ?? '127.0.0.1' }}</div></div>
                        <div class="kv-row"><div class="kv-key">User Agent</div><div class="kv-val">{{ $request_info['user_agent'] ?? 'Unknown' }}</div></div>
                        @if(!empty($request_info['referer']))
                        <div class="kv-row"><div class="kv-key">Referer</div><div class="kv-val">{{ $request_info['referer'] }}</div></div>
                        @endif
                        @if(!empty($request_info['content_type']))
                        <div class="kv-row"><div class="kv-key">Content-Type</div><div class="kv-val">{{ $request_info['content_type'] }}</div></div>
                        @endif
                        @if(!empty($request_info['query']))
                        <div class="kv-row"><div class="kv-key">Query Params</div><div class="kv-val"><pre class="bg-dark-500 p-3 rounded text-[11px] border border-dark-50/20 mt-1">{{ json_encode($request_info['query'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></div>
                        @endif
                    </div>

                    <!-- Headers -->
                    <div id="tab-headers" class="tab-pane hidden">
                        <?php $headers = function_exists('getallheaders') ? getallheaders() : []; ?>
                        @if(!empty($headers))
                            @foreach($headers as $key => $val)
                            <div class="kv-row"><div class="kv-key">{{ $key }}</div><div class="kv-val">{{ $val }}</div></div>
                            @endforeach
                        @else
                            @foreach($_SERVER as $k => $v)
                                @if(str_starts_with($k, 'HTTP_'))
                                <div class="kv-row"><div class="kv-key">{{ str_replace('HTTP_', '', $k) }}</div><div class="kv-val">{{ $v }}</div></div>
                                @endif
                            @endforeach
                        @endif
                    </div>

                    <!-- Body -->
                    <div id="tab-body" class="tab-pane hidden">
                        @if(!empty($_POST))
                            @foreach($_POST as $key => $val)
                            <div class="kv-row">
                                <div class="kv-key">{{ $key }}</div>
                                <div class="kv-val">
                                    @if(in_array($key, ['password', 'password_confirmation', 'token', 'secret', 'credit_card', 'cvv', '_token']))
                                        <span class="text-zinc-600 italic">••••••••</span>
                                    @else
                                        {{ is_array($val) ? json_encode($val) : $val }}
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="flex items-center justify-center py-12 text-zinc-600 text-sm">No POST data</div>
                        @endif
                    </div>

                    <!-- Session -->
                    <div id="tab-session" class="tab-pane hidden">
                        @if(!empty($_SESSION))
                            @foreach($_SESSION as $key => $val)
                            <div class="kv-row">
                                <div class="kv-key">{{ $key }}</div>
                                <div class="kv-val">
                                    @if(in_array($key, ['_token', 'csrf_token']))
                                        <span class="text-zinc-600 italic">••••••••</span>
                                    @else
                                        <pre class="text-[11px] whitespace-pre-wrap">{{ is_scalar($val) ? $val : json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="flex items-center justify-center py-12 text-zinc-600 text-sm">No session data</div>
                        @endif
                    </div>

                    <!-- Cookies -->
                    <div id="tab-cookies" class="tab-pane hidden">
                        @if(!empty($_COOKIE))
                            @foreach($_COOKIE as $key => $val)
                            <div class="kv-row"><div class="kv-key">{{ $key }}</div><div class="kv-val">{{ is_scalar($val) ? $val : json_encode($val) }}</div></div>
                            @endforeach
                        @else
                            <div class="flex items-center justify-center py-12 text-zinc-600 text-sm">No cookies</div>
                        @endif
                    </div>

                    <!-- Environment -->
                    <div id="tab-env" class="tab-pane hidden">
                        <div class="kv-row"><div class="kv-key">PHP Version</div><div class="kv-val font-bold text-info">{{ $environment['php_version'] ?? PHP_VERSION }}</div></div>
                        <div class="kv-row"><div class="kv-key">App Environment</div><div class="kv-val"><span class="badge badge-app">{{ strtoupper($environment['app_env'] ?? 'local') }}</span></div></div>
                        <div class="kv-row"><div class="kv-key">Memory Usage</div><div class="kv-val">{{ $environment['memory_usage'] ?? 'N/A' }}</div></div>
                        <div class="kv-row"><div class="kv-key">Peak Memory</div><div class="kv-val">{{ $environment['memory_peak'] ?? 'N/A' }}</div></div>
                        <div class="kv-row"><div class="kv-key">PHP SAPI</div><div class="kv-val">{{ $environment['php_sapi'] ?? PHP_SAPI }}</div></div>
                        <div class="kv-row"><div class="kv-key">Server OS</div><div class="kv-val">{{ $environment['os'] ?? PHP_OS }}</div></div>
                        <div class="kv-row"><div class="kv-key">Server Software</div><div class="kv-val">{{ $environment['server_software'] ?? 'N/A' }}</div></div>
                        <div class="kv-row"><div class="kv-key">Timestamp</div><div class="kv-val">{{ $environment['timestamp'] ?? date('Y-m-d H:i:s') }}</div></div>
                        <div class="kv-row"><div class="kv-key">Framework</div><div class="kv-val text-accent font-bold">The Framework v5.0.1</div></div>
                    </div>

                    <!-- Context -->
                    <div id="tab-context" class="tab-pane hidden">
                        @if(!empty($exception_context))
                            @foreach($exception_context as $key => $val)
                            <div class="kv-row">
                                <div class="kv-key text-accent">{{ $key }}</div>
                                <div class="kv-val"><pre class="bg-dark-500 p-3 rounded text-[11px] border border-dark-50/20 overflow-auto">{{ is_string($val) ? $val : json_encode($val, JSON_PRETTY_PRINT) }}</pre></div>
                            </div>
                            @endforeach
                        @else
                            <div class="flex items-center justify-center py-12 text-zinc-600 text-sm">No context data — implement <code class="text-accent mx-1">context()</code> method on your exception</div>
                        @endif
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const frames = <?php echo json_encode($trace_parsed); ?>;
        const mainSnippet = <?php echo json_encode($code_snippet ?? []); ?>;
        const mainFile = <?php echo json_encode($file); ?>;
        const mainLine = <?php echo json_encode($line); ?>;
        let vendorVisible = true;

        function selectFrame(index, element) {
            document.querySelectorAll('.frame-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');

            if (index === -1) {
                renderSnippet(mainSnippet, mainFile, mainLine);
                return;
            }

            const frame = frames[index];
            renderSnippet(frame.snippet || {}, frame.file || 'Unknown', frame.line || 0);
        }

        function renderSnippet(snippet, file, line) {
            document.getElementById('current-file-path').innerText = file;
            document.getElementById('current-file-line').innerText = line || '?';

            const body = document.getElementById('code-snippet-body');
            if (!snippet || Object.keys(snippet).length === 0) {
                body.innerHTML = '<div class="flex items-center justify-center h-full text-zinc-600 text-sm">No source code available for this frame</div>';
                return;
            }

            let html = '';
            for (const [num, code] of Object.entries(snippet)) {
                const active = parseInt(num) === parseInt(line) ? 'active' : '';
                const safe = (code || ' ').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                html += `<div class="code-line ${active}"><div class="line-num">${num}</div><div class="line-code">${safe}</div></div>`;
            }
            body.innerHTML = html;

            setTimeout(() => {
                const el = body.querySelector('.active');
                if (el) el.scrollIntoView({ behavior: 'auto', block: 'center' });
            }, 20);
        }

        function switchTab(id, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => { p.classList.add('hidden'); p.classList.remove('block'); });
            if (btn) btn.classList.add('active');
            const pane = document.getElementById('tab-' + id);
            if (pane) { pane.classList.remove('hidden'); pane.classList.add('block'); }
        }

        function toggleVendor() {
            vendorVisible = !vendorVisible;
            document.querySelectorAll('.frame-item[data-is-vendor="1"]').forEach(el => {
                el.style.display = vendorVisible ? '' : 'none';
            });
            document.getElementById('vendor-toggle-text').innerText = vendorVisible ? 'Hide vendor frames' : 'Show vendor frames';
        }

        function copyError(btn) {
            const msg = document.getElementById('error-message').innerText;
            navigator.clipboard.writeText(msg).then(() => {
                btn.classList.add('copied');
                btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Copied!';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg> Copy';
                }, 2000);
            });
        }

        // Init
        renderSnippet(mainSnippet, mainFile, mainLine);
    </script>
</body>
</html>
