<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Error - The Framework</title>
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
                            red: '#F43F5E',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #18181b; color: #f4f4f5; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; overflow: hidden; margin: 0; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        
        .code-container { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.9rem; line-height: 1.6; }
        
        .tab-btn { padding: 0.875rem 1.5rem; color: #a1a1aa; font-size: 0.875rem; font-weight: 500; border-bottom: 2px solid transparent; transition: all 0.2s; background: transparent; }
        .tab-btn:hover { color: #f4f4f5; }
        .tab-btn.active { color: #F43F5E; border-bottom-color: #F43F5E; }

        .key-val { display: flex; border-bottom: 1px solid #2a2a2a; padding: 0.875rem 0; }
        .key-val:last-child { border-bottom: none; }
        .key-col { width: 30%; color: #a1a1aa; font-size: 0.875rem; font-weight: 500; }
        .val-col { width: 70%; font-family: monospace; color: #d4d4d8; word-break: break-all; font-size: 0.85rem; }
    </style>
</head>
<body class="flex flex-col h-screen antialiased">

    <!-- Header -->
    <header class="bg-dark-600 border-b border-dark-100 flex-shrink-0 flex items-center justify-between px-10 py-8 relative overflow-hidden shadow-md">
        <!-- Glow effect -->
        <div class="absolute top-0 left-0 w-full h-1 bg-brand-red"></div>
        <div class="absolute top-[-100px] left-[-50px] w-[400px] h-[400px] bg-brand-red/10 blur-[120px] rounded-full pointer-events-none"></div>

        <div class="flex-1 min-w-0 z-10">
            <h2 class="text-zinc-400 text-sm font-mono tracking-wider truncate mb-2 flex items-center gap-2">
                <svg class="w-4 h-4 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                DATABASE EXCEPTION
            </h2>
            <h1 class="text-3xl font-black text-zinc-100 leading-tight tracking-tight">
                {{ $message ?? 'Database connection or validation failure.' }}
            </h1>
        </div>
        
        <div class="text-right ml-10 shrink-0 z-10 flex flex-col items-end gap-3">
            <button onclick="location.reload()" class="inline-flex items-center justify-center px-4 py-2 bg-dark-400 hover:bg-dark-200 border border-dark-100 text-zinc-300 text-sm font-bold rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Retry Request
            </button>
            <div class="flex flex-col items-end gap-1">
                <span class="text-brand-red text-xs font-black uppercase tracking-widest">{{ $error_type ?? 'Query Error' }}</span>
                <span class="text-zinc-500 font-mono text-xs">{{ $request_info['method'] ?? 'GET' }} {{ $request_info['uri'] ?? '/' }}</span>
            </div>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="flex flex-1 overflow-hidden">
        
        <!-- Sidebar Diagnosis -->
        <aside class="w-[450px] bg-dark-400 border-r border-dark-100 flex flex-col shrink-0 z-20 shadow-xl overflow-y-auto scrollbar-hide p-6">
            <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mb-6 border-b border-dark-100 pb-2">Environment Configuration</h3>
            
            <div class="space-y-4">
                @php
                    $envVars = [
                        'DB_DRIVER' => $env_values['DB_DRIVER'] ?? 'mysql',
                        'DB_HOST' => $env_values['DB_HOST'] ?? 'not set',
                        'DB_PORT' => $env_values['DB_PORT'] ?? 'not set',
                        'DB_NAME' => $env_values['DB_NAME'] ?? 'not set',
                        'DB_USER' => $env_values['DB_USER'] ?? 'not set',
                    ];
                @endphp
                
                @foreach($envVars as $key => $val)
                <div class="p-3 bg-dark-500 rounded border border-dark-100 flex items-center justify-between shadow-inner">
                    <span class="text-zinc-400 font-mono text-xs font-bold">{{ $key }}</span>
                    @if($val === 'not set')
                        <span class="text-brand-red font-mono text-xs italic">{{ $val }}</span>
                    @else
                        <span class="text-blue-400 font-mono text-xs font-bold">{{ $val }}</span>
                    @endif
                </div>
                @endforeach
            </div>

            <h3 class="text-xs font-black text-zinc-400 uppercase tracking-widest mt-8 mb-4 border-b border-dark-100 pb-2">Troubleshooting</h3>
            
            <div class="space-y-3">
                <div class="flex items-start gap-3 p-4 bg-dark-500 rounded border border-dark-100">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-xs text-zinc-400 font-medium">Verify your <span class="text-zinc-200 font-mono">.env</span> file matches the requirements above.</p>
                </div>
                <div class="flex items-start gap-3 p-4 bg-dark-500 rounded border border-dark-100">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-xs text-zinc-400 font-medium">Ensure your Database service (MySQL/PostgreSQL) is running.</p>
                </div>
                <div class="flex items-start gap-3 p-4 bg-dark-500 rounded border border-dark-100">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-xs text-zinc-400 font-medium">Clear system cache by deleting <span class="text-zinc-200 font-mono">storage/cache</span> mapping.</p>
                </div>
            </div>

        </aside>

        <!-- Main Content Pane -->
        <main class="flex-1 bg-dark-300 flex flex-col overflow-hidden relative">
            
            <!-- Context Headers -->
            <div class="flex px-2 bg-dark-500 border-b border-dark-100 shrink-0 shadow-sm">
                <button class="tab-btn active uppercase tracking-wider text-xs" onclick="switchTab('query')">SQL Details</button>
                <button class="tab-btn uppercase tracking-wider text-xs" onclick="switchTab('request')">Request Status</button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-8 scrollbar-hide text-base">
                
                <!-- Query Tab -->
                <div id="tab-query" class="tab-content block">
                    <div class="mb-4 text-xs font-black text-zinc-500 uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                        Raw Query Execution
                    </div>
                    
                    @if(!empty($sql))
                    <div class="bg-dark-600 border border-dark-100 rounded-lg p-6 overflow-x-auto shadow-inner mb-8">
                        <pre class="code-container text-blue-300">{{ $sql }}</pre>
                    </div>
                    @else
                    <div class="bg-dark-500 border border-dark-100 rounded-lg p-6 mb-8 flex items-center justify-center">
                        <span class="text-zinc-500 text-sm italic">Failed before SQL was generated.</span>
                    </div>
                    @endif

                    <div class="mb-4 text-xs font-black text-zinc-500 uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                        Bindings
                    </div>
                    
                    @if(!empty($bindings))
                    <div class="bg-dark-600 border border-dark-100 rounded-lg p-6 overflow-x-auto shadow-inner">
                        <pre class="code-container text-amber-300">{{ json_encode($bindings, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                    @else
                    <div class="bg-dark-500 border border-dark-100 rounded-lg p-6 flex items-center justify-center">
                        <span class="text-zinc-500 text-sm italic">No bindings attached.</span>
                    </div>
                    @endif
                </div>

                <!-- Request Tab -->
                <div id="tab-request" class="tab-content hidden">
                    <div class="key-val">
                        <div class="key-col">URL</div>
                        <div class="val-col"><span class="bg-dark-100 px-1 py-0.5 rounded mr-2 text-zinc-500">{{ $request_info['method'] ?? 'GET' }}</span> <a href="{{ $request_info['uri'] ?? '/' }}" target="_blank" class="text-blue-400 hover:underline">{{ $request_info['uri'] ?? '/' }}</a></div>
                    </div>
                    <div class="key-val">
                        <div class="key-col">Client IP</div>
                        <div class="val-col">{{ $request_info['ip'] ?? 'Unknown' }}</div>
                    </div>
                    @if(!empty($request_info['query']))
                    <div class="key-val">
                        <div class="key-col">Query String</div>
                        <div class="val-col">
                            <pre class="bg-dark-100 p-3 rounded font-mono text-xs border border-dark-100">{{ json_encode($request_info['query'], JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    @endif
                </div>

            </div>

        </main>
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('block'));
            
            event.target.classList.add('active');
            document.getElementById('tab-' + tabId).classList.remove('hidden');
            document.getElementById('tab-' + tabId).classList.add('block');
        }
    </script>
</body>
</html>
