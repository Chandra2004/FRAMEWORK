@extends('Internal::layout')

@section('title', 'Web Tinker')

@section('terminal-content')
    <div class="flex flex-col h-full">
        <!-- Header Console -->
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2 text-warning-400">
                <i data-lucide="terminal-square" class="w-5 h-5"></i>
                <span class="font-bold tracking-wider">INTERACTIVE PHP SHELL</span>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="px-2 py-1 bg-slate-800 rounded border border-slate-700">Ctrl + Enter to Run</span>
            </div>
        </div>

        <!-- Output Terminal Window -->
        <div class="flex-grow bg-slate-950 rounded-lg border border-slate-800 p-4 mb-4 overflow-y-auto font-mono text-sm relative custom-scrollbar"
            id="tinker-history" style="min-height: 300px; max-height: 60vh;">

            <div class="text-slate-500 mb-4 select-none">
                <p>The Framework Tinker v1.0.0 (Web Edition)</p>
                <p>Type PHP code below and execute. Use <code class="text-slate-400">echo</code> or just execute an
                    expression.</p>
                <div class="h-px w-full bg-slate-800 my-2"></div>
            </div>

            <!-- Output will be appended here -->
        </div>

        <!-- Input Area -->
        <form id="tinker-form" class="relative group">
            <div
                class="absolute left-0 top-0 bottom-0 w-1 bg-warning-500 rounded-l-lg transition-all group-focus-within:bg-warning-400 opacity-50 group-focus-within:opacity-100">
            </div>

            <div
                class="flex items-start gap-0 bg-slate-900/50 border border-slate-700 rounded-lg overflow-hidden focus-within:border-warning-500/50 transition-colors">
                <div class="p-3 text-warning-500 pt-3 select-none bg-slate-900 border-r border-slate-800">
                    <span class="font-bold">>>></span>
                </div>

                <textarea id="tinker-code" name="code" rows="2"
                    class="w-full bg-transparent text-slate-200 p-3 font-mono text-sm focus:outline-none resize-none placeholder-slate-600 leading-relaxed"
                    placeholder="$user = \TheFramework\Models\User::first(); return $user;" spellcheck="false"></textarea>

                <button type="submit" id="btn-execute"
                    class="self-stretch px-6 bg-slate-800 hover:bg-warning-600 hover:text-white text-slate-400 border-l border-slate-700 transition-all flex items-center justify-center gap-2 group-btn">
                    <i data-lucide="play" class="w-4 h-4 fill-current"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Custom Scrollbar Style -->
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #020617;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('tinker-form');
            const codeInput = document.getElementById('tinker-code');
            const historyContainer = document.getElementById('tinker-history');
            const executeBtn = document.getElementById('btn-execute');

            // Initial Icon
            if (window.lucide) window.lucide.createIcons();

            // Auto resize textarea
            codeInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
                if (this.value === '') this.style.height = 'auto';
            });

            // Focus input
            codeInput.focus();

            codeInput.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    form.dispatchEvent(new Event('submit'));
                }
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const code = codeInput.value.trim();
                if (!code) return;

                // UI Loading State
                codeInput.classList.add('opacity-50');
                codeInput.readOnly = true;
                executeBtn.innerHTML =
                    '<div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>';
                executeBtn.disabled = true;

                // Add Command to History
                const entryDiv = document.createElement('div');
                entryDiv.className =
                    'mb-4 border-b border-slate-800/50 pb-4 last:border-0 last:mb-0 last:pb-0 animate-in fade-in slide-in-from-bottom-2 duration-300';
                entryDiv.innerHTML = `
                <div class="flex gap-2 mb-2 font-mono text-xs">
                    <span class="text-warning-500 font-bold">>>></span>
                    <span class="text-slate-300">${escapeHtml(code)}</span>
                </div>
            `;
                historyContainer.appendChild(entryDiv);

                // Execute
                fetch('/_system/tinker', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            code: code
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        let outputHtml = '';

                        // Captured Output (echo)
                        if (data.output) {
                            outputHtml +=
                                `<div class="text-slate-400 mb-1 pl-6 border-l-2 border-slate-700 ml-1 whitespace-pre-wrap">${data.output}</div>`;
                        }

                        // Return Result
                        if (data.result !== null) {
                            outputHtml +=
                                `<div class="text-emerald-400 pl-6 ml-1 whitespace-pre-wrap font-bold text-glow">${data.result}</div>`;
                        } else if (!data.output) {
                            outputHtml += `<div class="text-slate-600 pl-6 ml-1 italic">null</div>`;
                        }

                        entryDiv.innerHTML += outputHtml;

                        // Scroll to bottom
                        historyContainer.scrollTop = historyContainer.scrollHeight;
                        codeInput.value = '';
                        codeInput.style.height = 'auto'; // reset height
                    })
                    .catch(err => {
                        entryDiv.innerHTML +=
                            `<div class="text-rose-500 pl-6 ml-1 whitespace-pre-wrap border-l-2 border-rose-900 bg-rose-950/20 p-2 text-xs">${err}</div>`;
                    })
                    .finally(() => {
                        // Reset UI
                        codeInput.classList.remove('opacity-50');
                        codeInput.readOnly = false;
                        executeBtn.innerHTML =
                        '<i data-lucide="play" class="w-4 h-4 fill-current"></i>';
                        executeBtn.disabled = false;
                        if (window.lucide) window.lucide.createIcons();
                        codeInput.focus();
                    });
            });

            function escapeHtml(text) {
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
@endpush
