<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ url('/file/shared/favicon-debug.ico') }}">
    <title>THE-FRAMEWORK | System Command Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'JetBrains Mono', monospace;
            background-color: #020617;
            color: #94a3b8;
        }

        .terminal-window {
            background-color: #0f172a;
            border: 1px solid #1e293b;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .text-glow {
            text-shadow: 0 0 10px rgba(6, 182, 212, 0.5);
        }

        .cursor-blink {
            animation: blink 1s step-end infinite;
        }

        @keyframes blink {

            from,
            to {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0f172a;
        }

        ::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #334155;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col p-4 md:p-8">
    <div class="max-w-5xl w-full mx-auto flex-grow flex flex-col">
        <!-- Header / Navigation -->
        <header class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 bg-cyan-500/10 rounded-xl flex items-center justify-center border border-cyan-500/20">
                    <i data-lucide="terminal" class="w-6 h-6 text-cyan-400"></i>
                </div>
                <div>
                    <h1 class="text-white font-bold tracking-tight">THE-FRAMEWORK</h1>
                    <p class="text-[10px] text-cyan-500 uppercase font-bold tracking-widest">System Control Console
                        v5.1.0</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden sm:flex flex-col items-end">
                    <span class="text-[10px] text-slate-500 uppercase font-bold">Client IP</span>
                    <span class="text-xs text-slate-300">{{ \TheFramework\Helpers\Helper::get_client_ip() }}</span>
                </div>
                <a href="{{ url('/') }}"
                    class="p-2 hover:bg-slate-800 rounded-lg transition-colors text-slate-400 hover:text-white"
                    title="Return to Home">
                    <i data-lucide="home" class="w-5 h-5"></i>
                </a>
            </div>
        </header>

        <!-- Main Terminal -->
        <main class="terminal-window rounded-2xl flex-grow overflow-hidden flex flex-col">
            <!-- Terminal Title Bar -->
            <div class="bg-slate-900/50 px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-rose-500/50"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-500/50"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-500/50"></div>
                    </div>
                    <span class="ml-2 text-xs text-slate-500 font-medium">bash &mdash; framework-cli</span>
                </div>
                <div class="text-[10px] text-slate-600 font-bold uppercase">
                    {{ date('H:i:s') }}
                </div>
            </div>

            <!-- Terminal Content Area -->
            <div class="p-6 flex-grow overflow-y-auto font-medium text-sm leading-relaxed">
                @yield('terminal-content')
            </div>

            <!-- Terminal Footer Status -->
            <div
                class="bg-slate-900/50 px-4 py-2 border-t border-slate-800 flex items-center justify-between text-[10px]">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span class="text-slate-500 uppercase font-bold">DB Status:</span>
                        <span class="text-emerald-500 font-bold">Connected</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                        <span class="text-slate-500 uppercase font-bold">Memory:</span>
                        <span class="text-cyan-500 font-bold">{{ round(memory_get_usage() / 1024 / 1024, 2) }}MB</span>
                    </span>
                </div>
                <div class="text-slate-600">
                    PHP v{{ PHP_VERSION }}
                </div>
            </div>
        </main>

        <footer class="mt-6 text-center">
            <p class="text-[11px] text-slate-600">
                &copy; {{ date('Y') }} THE-FRAMEWORK System Tools. Unauthorized access is recorded.
            </p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();
        });
    </script>
</body>

</html>
