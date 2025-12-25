<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warning - {{ $error_code ?? 200 }} | The Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-950 via-amber-950/10 to-slate-950 min-h-screen text-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="border-b border-slate-800/50 bg-slate-900/30 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-500"></i>
                        <span class="text-xl font-semibold bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                            The Framework
                        </span>
                    </div>
                    <a href="https://github.com/Chandra2004/FRAMEWORK" target="_blank" 
                       class="text-slate-400 hover:text-cyan-400 transition-colors">
                        <i data-lucide="github" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-4xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
            <div class="space-y-6 animate-fade-in">
                <!-- Warning Header -->
                <div class="text-center space-y-4">
                    <div class="inline-flex items-center gap-3 px-4 py-2 bg-amber-500/10 border border-amber-500/20 rounded-full">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500"></i>
                        <span class="text-amber-400 font-medium">{{ $severity_name ?? 'Warning' }}</span>
                        @if (!empty($error_code))
                        <span class="text-xs text-amber-500/70">HTTP {{ $error_code }}</span>
                        @endif
                    </div>
                    <h1 class="text-5xl sm:text-6xl font-bold bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">
                        Framework Warning
                    </h1>
                    <p class="text-lg text-slate-300 max-w-xl mx-auto bg-amber-500/10 border border-amber-500/20 rounded-xl px-6 py-4">
                        {{ $message }}
                    </p>
                </div>

                <!-- Error Details Grid -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="file-code" class="w-4 h-4 text-amber-400"></i>
                            <span class="text-sm font-medium text-slate-400">File Location</span>
                        </div>
                        <p class="text-sm font-mono text-slate-200 break-all">{{ basename($file) }}</p>
                        <p class="text-xs text-slate-500 mt-1 break-all">{{ $file }}</p>
                        <p class="text-xs text-amber-400 mt-2 font-semibold">Line: {{ $line }}</p>
                    </div>
                    <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-amber-400"></i>
                            <span class="text-sm font-medium text-slate-400">Severity</span>
                        </div>
                        <p class="text-sm text-amber-300 font-mono font-semibold">{{ $severity_name ?? 'E_WARNING' }}</p>
                        <p class="text-xs text-slate-500 mt-1">Code: {{ $severity ?? 0 }}</p>
                    </div>
                </div>

                <!-- Code Snippet -->
                @if (!empty($code_snippet))
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                    <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="code" class="w-4 h-4 text-amber-400"></i>
                            <span class="text-sm font-medium text-slate-300">{{ basename($file) }}</span>
                        </div>
                        <span class="text-xs text-slate-500">Lines {{ min(array_keys($code_snippet)) }}-{{ max(array_keys($code_snippet)) }}</span>
                    </div>
                    <div class="p-4 overflow-x-auto max-h-96 overflow-y-auto">
                        <pre class="text-sm font-mono"><code>@foreach ($code_snippet as $lineNum => $codeLine)
<span class="{{ $lineNum == $line ? 'bg-amber-500/20 text-amber-300 border-l-2 border-amber-500 pl-2 -ml-2' : 'text-slate-300' }}">{{ str_pad($lineNum, 4, ' ', STR_PAD_LEFT) }} | {!! htmlspecialchars(rtrim($codeLine)) !!}</span>
@endforeach</code></pre>
                    </div>
                </div>
                @else
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                    <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="code" class="w-4 h-4 text-amber-400"></i>
                            <span class="text-sm font-medium text-slate-300">{{ basename($file) }}</span>
                        </div>
                        <span class="text-xs text-slate-500">Line {{ $line }}</span>
                    </div>
                    <div class="p-4 overflow-x-auto max-h-96 overflow-y-auto">
                        @php
                            if (file_exists($file) && is_readable($file)) {
                                $lines = file($file);
                                $start = max(0, $line - 5);
                                $end = min(count($lines), $line + 4);
                            } else {
                                $lines = [];
                                $start = $end = 0;
                            }
                        @endphp
                        <pre class="text-sm font-mono"><code>@for ($i = $start; $i <= $end; $i++)
<span class="{{ $i + 1 == $line ? 'bg-amber-500/20 text-amber-300 border-l-2 border-amber-500 pl-2 -ml-2' : 'text-slate-300' }}">{{ str_pad($i + 1, 4, ' ', STR_PAD_LEFT) }} | {!! htmlspecialchars($lines[$i] ?? '') !!}</span>
@endfor</code></pre>
                    </div>
                </div>
                @endif

                <!-- Request Info -->
                @if (!empty($request_info))
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl overflow-hidden">
                    <div class="bg-slate-900/50 px-5 py-3 border-b border-slate-700/50 flex items-center gap-2">
                        <i data-lucide="globe" class="w-4 h-4 text-amber-400"></i>
                        <span class="text-sm font-medium text-slate-300">Request Information</span>
                    </div>
                    <div class="p-4 grid md:grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-slate-500">Method:</span>
                            <span class="text-slate-200 ml-2 font-mono">{{ $request_info['method'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">URI:</span>
                            <span class="text-slate-200 ml-2 font-mono text-xs break-all">{{ $request_info['uri'] }}</span>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Actions -->
                <div class="flex flex-wrap gap-3 justify-center pt-4">
                    <button onclick="history.back()" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Go Back
                    </button>
                    <button onclick="window.location.reload()" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg font-medium transition-colors">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        Reload
                    </button>
                    <a href="{{ url('/') }}" 
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                        <i data-lucide="home" class="w-4 h-4"></i>
                        Home
                    </a>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-800/50 mt-12 py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-slate-500">
                <p>&copy; {{ date('Y') }} The Framework. Created with ❤️ by <a href="https://github.com/Chandra2004" target="_blank" class="text-cyan-400 hover:underline">Chandra Tri Antomo</a></p>
            </div>
        </footer>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>