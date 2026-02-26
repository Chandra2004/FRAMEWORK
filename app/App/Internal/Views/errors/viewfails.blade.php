<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $class ?? 'View Error' }} - The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            100: '#2A2A2A',
                            200: '#242424',
                            300: '#1e1e1e', 
                            400: '#18181b', 
                            500: '#121212', 
                            600: '#09090b',
                        },
                        brand: {
                            amber: '#F59E0B',
                            amber_glow: 'rgba(245, 158, 11, 0.15)'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #18181b; color: #f4f4f5; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; overflow: hidden; margin: 0; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        
        .code-container { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.85rem; line-height: 1.6; }
        .code-line { display: flex; padding: 0 1rem; cursor: text; transition: background 0.1s; border-left: 2px solid transparent;}
        .code-line:hover { background-color: rgba(255, 255, 255, 0.03); }
        .code-line.active { background-color: var(--tw-color-brand-amber_glow, rgba(245, 158, 11, 0.15)); border-left-color: #F59E0B; }
        .code-line.active .line-num { color: #F59E0B; font-weight: bold; }
        .line-num { width: 3.5rem; text-align: right; padding-right: 1.5rem; color: #52525b; user-select: none; border-right: 1px solid #3f3f46; margin-right: 1.5rem; }
        .line-code { white-space: pre; color: #e4e4e7; }

        .frame-item { padding: 1rem 1.5rem; cursor: pointer; border-bottom: 1px solid #2a2a2a; border-right: 2px solid transparent; transition: background 0.15s; }
        .frame-item:hover { background-color: #242424; }
        .frame-item.active { background-color: #242424; border-right-color: #F59E0B; }
        
        .tab-btn { padding: 0.875rem 1.5rem; color: #a1a1aa; font-size: 0.875rem; font-weight: 500; border-bottom: 2px solid transparent; transition: all 0.2s; background: transparent; }
        .tab-btn:hover { color: #f4f4f5; }
        .tab-btn.active { color: #F59E0B; border-bottom-color: #F59E0B; }

        .key-val { display: flex; border-bottom: 1px solid #2a2a2a; padding: 0.875rem 0; }
        .key-val:last-child { border-bottom: none; }
        .key-col { width: 25%; color: #a1a1aa; font-size: 0.875rem; font-weight: 500; }
        .val-col { width: 75%; font-family: monospace; color: #d4d4d8; word-break: break-all; font-size: 0.85rem; }
    </style>
</head>
<body class="flex flex-col h-screen antialiased">

    <!-- Header -->
    <header class="bg-dark-600 border-b border-dark-100 flex-shrink-0 flex items-center justify-between px-10 py-8 relative overflow-hidden shadow-md">
        <!-- Glow effect -->
        <div class="absolute top-0 left-0 w-full h-1 bg-brand-amber"></div>
        <div class="absolute top-[-100px] left-[-50px] w-[400px] h-[400px] bg-brand-amber/10 blur-[120px] rounded-full pointer-events-none"></div>

        <div class="flex-1 min-w-0 z-10">
            <h2 class="text-zinc-400 text-sm font-mono tracking-wider truncate mb-2 flex items-center gap-2 uppercase font-black">
                <svg class="w-4 h-4 text-brand-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                VIEW RENDERING WARNING
            </h2>
            <h1 class="text-3xl font-black text-zinc-100 leading-tight tracking-tight">
                {{ $message }}
            </h1>
        </div>
        
        <div class="text-right ml-10 shrink-0 z-10 flex flex-col items-end gap-3">
            <button onclick="history.back()" class="inline-flex items-center justify-center px-4 py-2 bg-dark-400 hover:bg-dark-200 border border-dark-100 text-zinc-300 text-sm font-bold rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Go Back
            </button>
            <div class="flex items-center gap-2 text-zinc-400 font-mono text-xs mt-1">
                <span class="bg-brand-amber/10 border border-brand-amber/20 text-brand-amber px-2.5 py-1 rounded shadow-inner uppercase font-bold">{{ $class ?? 'E_WARNING' }}</span>
                <span>Blade Compiler Engine</span>
            </div>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="flex flex-1 overflow-hidden">
        
        <!-- Sidebar Stack Trace -->
        <aside class="w-[450px] bg-dark-400 border-r border-dark-100 flex flex-col shrink-0 z-20 shadow-xl">
            <div class="px-6 py-4 bg-dark-500 border-b border-dark-100 flex items-center justify-between shrink-0">
                <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest">Compiler Stack Trace</h3>
                <span class="text-[10px] bg-dark-100 text-zinc-400 px-2 py-1 rounded-full font-bold">{{ count($trace_parsed ?? []) + 1 }} FRAMES</span>
            </div>
            
            <div class="overflow-y-auto flex-1 scrollbar-hide pb-10" id="frames-list">
                
                <!-- PRIMARY VIEW ERROR FRAME -->
                <div class="frame-item active" onclick="selectFrame(0, this)">
                    <div class="flex justify-between items-start mb-1.5">
                        <div class="font-mono text-[13px] text-zinc-200 truncate pr-2 font-semibold">
                            Blade::render()
                        </div>
                        <span class="shrink-0 text-[9px] px-1.5 py-0.5 bg-brand-amber/20 text-brand-amber rounded font-black uppercase tracking-wider border border-brand-amber/20">Template</span>
                    </div>
                    <div class="text-xs text-zinc-500 font-mono truncate flex items-center gap-1.5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        {{ basename($file) }}:{{ $line }}
                    </div>
                </div>

                @if(!empty($trace_parsed))
                @foreach($trace_parsed as $index => $frame)
                <div class="frame-item" onclick="selectFrame({{ $index + 1 }}, this)">
                    <div class="flex justify-between items-start mb-1.5">
                        <div class="font-mono text-[13px] text-zinc-200 truncate pr-2 font-semibold">
                            @if($frame['class'])
                                <span class="text-zinc-500 font-normal">{{ $frame['class'] }}{{ $frame['type'] }}</span>{{ $frame['function'] }}
                            @else
                                {{ $frame['function'] }}
                            @endif
                        </div>
                        @if($frame['is_app'])
                            <span class="shrink-0 text-[9px] px-1.5 py-0.5 bg-dark-100 text-zinc-300 rounded font-black uppercase tracking-wider border border-dark-100">App</span>
                        @else
                            <span class="shrink-0 text-[9px] px-1.5 py-0.5 bg-dark-100 text-zinc-500 rounded font-black uppercase tracking-wider border border-dark-100">Vendor</span>
                        @endif
                    </div>
                    <div class="text-xs text-zinc-500 font-mono truncate flex items-center gap-1.5">
                        <svg class="w-3 h-3 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        {{ basename($frame['file']) }}:{{ $frame['line'] ?? '?' }}
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </aside>

        <!-- Main Content Pane -->
        <main class="flex-1 bg-dark-300 flex flex-col overflow-hidden relative">
            
            <!-- Code Snippet Viewer -->
            <div class="h-[60%] flex flex-col bg-[#1e1e1e] border-b border-dark-100">
                <div class="px-6 py-3 border-b border-dark-100 flex items-center bg-dark-400 shrink-0">
                    <svg class="w-4 h-4 text-zinc-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                    <span class="text-[13px] font-mono text-zinc-400" id="current-file-path">{{ $file }}</span>
                    <span class="text-[13px] font-mono text-zinc-600 mx-1.5">:</span>
                    <span class="text-[13px] font-mono text-brand-amber font-bold" id="current-file-line">{{ $line }}</span>
                </div>
                
                <div class="flex-1 overflow-auto py-4 code-container scrollbar-hide" id="code-snippet-body">
                    <!-- Snippet dynamically loaded via JS -->
                </div>
            </div>

            <!-- Detail Tabs -->
            <div class="flex-1 flex flex-col overflow-hidden bg-dark-400">
                <div class="flex px-2 bg-dark-500 border-b border-dark-100 shrink-0 shadow-sm">
                    <button class="tab-btn active uppercase tracking-wider text-xs" onclick="switchTab('environment')">Environment</button>
                    <button class="tab-btn uppercase tracking-wider text-xs" onclick="switchTab('context')">Variables Sent to View</button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-8 scrollbar-hide">
                    
                    <!-- Environment Tab -->
                    <div id="tab-environment" class="tab-content block">
                        <div class="key-val">
                            <div class="key-col">PHP Version</div>
                            <div class="val-col font-bold">{{ $environment['php_version'] ?? PHP_VERSION }}</div>
                        </div>
                        <div class="key-val">
                            <div class="key-col">App Environment</div>
                            <div class="val-col"><span class="bg-brand-red/10 text-brand-red px-2 py-1 rounded text-xs font-bold uppercase">{{ $environment['app_env'] ?? 'local' }}</span></div>
                        </div>
                        <div class="flex items-center gap-3 p-4 bg-dark-500 rounded border border-dark-100 mt-6">
                            <svg class="w-5 h-5 text-brand-amber flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <p class="text-xs text-zinc-400 leading-relaxed font-medium">The execution continues since this is just an <b>E_WARNING</b>, but the layout rendering may be compromised. Check your Blade Template variables.</p>
                        </div>
                    </div>

                    <!-- Context Tab -->
                    <div id="tab-context" class="tab-content hidden">
                        @if(!empty($exception_context))
                            @foreach($exception_context as $key => $val)
                            <div class="key-val">
                                <div class="key-col text-brand-amber">{{ $key }}</div>
                                <div class="val-col">
                                    <pre class="bg-dark-100 p-3 rounded text-[11px] overflow-auto border border-dark-100 text-zinc-300">{{ is_string($val) ? $val : json_encode($val, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="flex items-center justify-center h-20 bg-dark-100 rounded border border-dashed border-dark-100">
                                <span class="text-zinc-500 text-sm font-medium">No bound parameters in this view scope.</span>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </main>
    </div>

    <script>
        // Combine the primary view error with the trace behind it
        const primaryFrame = {
            file: <?php echo json_encode($file); ?>,
            line: <?php echo json_encode($line); ?>,
            snippet: <?php echo json_encode($code_snippet); ?>
        };
        
        const backendFrames = <?php echo json_encode($trace_parsed ?? []); ?>;
        const frames = [primaryFrame, ...backendFrames];
        
        function selectFrame(index, element) {
            // Update Active Class
            document.querySelectorAll('.frame-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            
            const frame = frames[index];
            
            // Update Header
            document.getElementById('current-file-path').innerText = frame.file || 'Unknown File';
            document.getElementById('current-file-line').innerText = frame.line || '?';
            
            // Render Snippet
            const snippetBody = document.getElementById('code-snippet-body');
            snippetBody.innerHTML = '';
            
            if (frame.snippet && Object.keys(frame.snippet).length > 0) {
                let html = '';
                for (const [lineNum, code] of Object.entries(frame.snippet)) {
                    const isActive = parseInt(lineNum) === parseInt(frame.line) ? 'active' : '';
                    const safeCode = code.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    
                    html += `
                        <div class="code-line ${isActive}">
                            <div class="line-num">${lineNum}</div>
                            <div class="line-code">${safeCode || ' '}</div>
                        </div>
                    `;
                }
                snippetBody.innerHTML = html;
                
                // Scroll to active line
                setTimeout(() => {
                    const activeLine = snippetBody.querySelector('.active');
                    if(activeLine) {
                        activeLine.scrollIntoView({ behavior: 'auto', block: 'center' });
                    }
                }, 10);
            } else {
                snippetBody.innerHTML = `
                    <div class="flex items-center justify-center h-full text-zinc-500 text-sm font-medium">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                        No code snippet available for this frame.
                    </div>
                `;
            }
        }
        
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('block'));
            
            event.target.classList.add('active');
            document.getElementById('tab-' + tabId).classList.remove('hidden');
            document.getElementById('tab-' + tabId).classList.add('block');
        }

        // Initialize First Frame
        if(frames.length > 0) {
            selectFrame(0, document.querySelector('.frame-item'));
        }
    </script>
</body>
</html>
