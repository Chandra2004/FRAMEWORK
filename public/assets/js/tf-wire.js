/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  TFWire.js — Client-Side Engine v2.0                         ║
 * ║  License: MIT                                                ║
 * ║                                                              ║
 * ║  BEYOND LIVEWIRE:                                            ║
 * ║  ✅ Lightweight DOM Morphing (only update changed nodes)     ║
 * ║  ✅ Offline Queue (queue actions when offline, replay later) ║
 * ║  ✅ Optimistic UI (instant feedback before server responds)  ║
 * ║  ✅ Prefetch on Hover (preload components on mouseover)     ║
 * ║  ✅ File Upload with Progress                                ║
 * ║  ✅ State Persistence (localStorage / sessionStorage)        ║
 * ║  ✅ Request Deduplication & Abort                            ║
 * ║  ✅ Keyboard Shortcuts                                       ║
 * ║  ✅ Auto-dismiss Toasts with Animation                       ║
 * ║  ✅ Polling with Visibility Awareness                        ║
 * ║  ✅ Loading States (show/hide/class/target-specific)        ║
 * ║                                                              ║
 * ║  Directives:                                                 ║
 * ║  tf-wire:click="method"          Server method call          ║
 * ║  tf-wire:click="method(1,'a')"   With parameters             ║
 * ║  tf-wire:submit="method"         Form submit handler         ║
 * ║  tf-wire:model="property"        Two-way binding             ║
 * ║  tf-wire:model.lazy="prop"       Binding on blur only        ║
 * ║  tf-wire:model.debounce.500ms    Debounced binding           ║
 * ║  tf-wire:confirm="Message?"      Confirm before action       ║
 * ║  tf-wire:loading                 Show during request         ║
 * ║  tf-wire:loading.remove          Hide during request         ║
 * ║  tf-wire:loading.class="dim"     Add class during request    ║
 * ║  tf-wire:target="method"         Loading for specific act    ║
 * ║  tf-wire:keydown.enter="method"  Keyboard shortcut           ║
 * ║  tf-wire:offline                 Show when offline           ║
 * ║  tf-wire:prefetch                Prefetch on hover           ║
 * ║  tf-wire:optimistic.remove       Remove el before response   ║
 * ║  tf-wire:optimistic.class="dim"  Add class before response   ║
 * ║  tf-wire:upload.progress="field" Show upload progress        ║
 * ║  tf-wire:upload.preview="field"  Show image preview          ║
 * ║  data-tf-poll="5000"             Auto-refresh interval       ║
 * ║  data-tf-persist                 Persist state to browser     ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
(function () {
    'use strict';

    const ENDPOINT = '/tfwire/handle';
    const DEBOUNCE_DEFAULT = 300;
    const VERSION = '2.0.0';

    // ─── Registries ─────────────────────────────────────────
    const activeRequests = new Map();
    const debounceTimers = new Map();
    const pollTimers = new Map();
    const offlineQueue = [];
    const prefetchCache = new Map();

    // ╔══════════════════════════════════════════════════════════╗
    // ║  CORE: Send Action to Server                             ║
    // ╚══════════════════════════════════════════════════════════╝
    async function sendAction(frameEl, action, extraData = {}, options = {}) {
        if (!frameEl) return;

        // Offline queue
        if (!navigator.onLine) {
            offlineQueue.push({ frameId: frameEl.id, action, extraData, options });
            showOfflineIndicator();
            return;
        }

        const state = frameEl.querySelector('input[name="_tf_state"]')?.value || '';
        const id    = frameEl.querySelector('input[name="_tf_id"]')?.value || '';
        const cls   = frameEl.querySelector('input[name="_tf_class"]')?.value || '';

        if (!cls) return;

        // Abort prev request for same component (deduplication)
        if (activeRequests.has(id)) {
            activeRequests.get(id).abort();
        }

        const controller = new AbortController();
        activeRequests.set(id, controller);

        // Build request
        const formData = new FormData();
        formData.append('_tf_state', state);
        formData.append('_tf_id', id);
        formData.append('_tf_class', cls);
        if (action) formData.append('_tf_action', action);

        // Collect tf-wire:model values
        collectModelValues(frameEl, formData);

        // Merge extra data
        Object.entries(extraData).forEach(([k, v]) => {
            formData.append(k, typeof v === 'object' ? JSON.stringify(v) : v);
        });

        // Persist state
        savePersistState(frameEl);

        // Show loading
        showLoading(frameEl, action);

        // Optimistic UI
        if (options.optimisticEl) {
            applyOptimistic(options.optimisticEl);
        }

        // CSRF Token logic
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const headers = {
            'Accept': 'text/vnd.turbo-stream.html',
            'X-TFWire': '1',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

        try {
            const res = await fetch(ENDPOINT, {
                method: 'POST',
                body: formData,
                signal: controller.signal,
                headers: headers,
            });

            // Turbo-Location redirect
            const turboLoc = res.headers.get('Turbo-Location');
            if (turboLoc) {
                window.Turbo ? Turbo.visit(turboLoc) : (window.location.href = turboLoc);
                return;
            }

            const html = await res.text();
            if (!html || !res.ok) return;

            // Morph or process streams
            if (window.Turbo?.renderStreamMessage) {
                Turbo.renderStreamMessage(html);
            } else {
                processStreams(html);
            }

            // 🔥 Event untuk re-init library pihak ke-3 (Lucide, dll)
            document.dispatchEvent(new CustomEvent('tfwire:dom-updated'));

        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('[TFWire] Request failed:', err);

            // Revert optimistic UI on error
            if (options.optimisticEl) {
                revertOptimistic(options.optimisticEl);
            }
        } finally {
            activeRequests.delete(id);
            hideLoading(frameEl, action);
        }
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  DOM MORPHING — Lightweight (Only Update Changed Nodes)  ║
    // ╚══════════════════════════════════════════════════════════╝
    function morphElement(oldEl, newHtml) {
        const template = document.createElement('template');
        template.innerHTML = newHtml.trim();
        const newEl = template.content.firstElementChild;

        if (!newEl || !oldEl) return;

        // Morph attributes
        morphAttributes(oldEl, newEl);

        // Morph children
        morphChildren(oldEl, newEl);
    }

    function morphAttributes(from, to) {
        // Remove old attributes
        for (const attr of [...from.attributes]) {
            if (!to.hasAttribute(attr.name)) {
                from.removeAttribute(attr.name);
            }
        }
        // Add/update new attributes
        for (const attr of [...to.attributes]) {
            if (from.getAttribute(attr.name) !== attr.value) {
                from.setAttribute(attr.name, attr.value);
            }
        }
    }

    function morphChildren(from, to) {
        // Preserve focused/active elements
        const activeEl = document.activeElement;
        const activeId = activeEl?.id;
        const activeValue = activeEl?.value;
        const selStart = activeEl?.selectionStart;
        const selEnd = activeEl?.selectionEnd;

        // Simple but effective: if IDs match, morph recursively; otherwise replace
        const fromNodes = [...from.childNodes];
        const toNodes = [...to.childNodes];

        // Remove extra old nodes
        while (from.childNodes.length > toNodes.length) {
            from.removeChild(from.lastChild);
        }

        toNodes.forEach((toNode, i) => {
            const fromNode = fromNodes[i];

            if (!fromNode) {
                from.appendChild(toNode.cloneNode(true));
                return;
            }

            // Text nodes
            if (toNode.nodeType === 3) {
                if (fromNode.nodeType === 3) {
                    if (fromNode.textContent !== toNode.textContent) {
                        fromNode.textContent = toNode.textContent;
                    }
                } else {
                    from.replaceChild(toNode.cloneNode(true), fromNode);
                }
                return;
            }

            // Element nodes
            if (toNode.nodeType === 1) {
                if (fromNode.nodeType !== 1 || fromNode.tagName !== toNode.tagName) {
                    from.replaceChild(toNode.cloneNode(true), fromNode);
                    return;
                }

                // Skip active input elements to preserve user input
                if (fromNode === activeEl && (fromNode.tagName === 'INPUT' || fromNode.tagName === 'TEXTAREA' || fromNode.tagName === 'SELECT')) {
                    morphAttributes(fromNode, toNode);
                    return;
                }

                morphAttributes(fromNode, toNode);
                morphChildren(fromNode, toNode);
            }
        });

        // Restore focus
        if (activeId) {
            const el = document.getElementById(activeId);
            if (el) {
                el.focus();
                if (typeof selStart === 'number' && el.setSelectionRange) {
                    try { el.setSelectionRange(selStart, selEnd); } catch {}
                }
            }
        }
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  MODEL VALUE COLLECTOR                                   ║
    // ╚══════════════════════════════════════════════════════════╝
    function collectModelValues(frameEl, formData) {
        const selectors = ['[tf-wire\\:model]', '[tf-wire\\:model\\.lazy]', '[tf-wire\\:model\\.debounce]'];
        selectors.forEach(sel => {
            frameEl.querySelectorAll(sel).forEach(input => {
                const attr = input.getAttributeNames().find(a => a.startsWith('tf-wire:model'));
                if (!attr) return;
                const prop = input.getAttribute(attr);
                let val;
                if (input.type === 'checkbox') val = input.checked ? '1' : '0';
                else if (input.type === 'radio') val = input.checked ? input.value : '';
                else if (input.type === 'file') return; // Handled separately
                else val = input.value;
                formData.append('tf_model_' + prop, val);
            });
        });
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  OPTIMISTIC UI                                           ║
    // ╚══════════════════════════════════════════════════════════╝
    function applyOptimistic(el) {
        const snapshot = el.outerHTML;
        el.dataset.tfOptimisticSnapshot = snapshot;

        if (el.hasAttribute('tf-wire:optimistic.remove')) {
            el.style.transition = 'all 0.3s ease';
            el.style.opacity = '0';
            el.style.transform = 'scale(0.95)';
            setTimeout(() => { el.style.display = 'none'; }, 300);
        }

        if (el.hasAttribute('tf-wire:optimistic.class')) {
            el.classList.add(el.getAttribute('tf-wire:optimistic.class'));
        }
    }

    function revertOptimistic(el) {
        if (el.dataset.tfOptimisticSnapshot) {
            el.outerHTML = el.dataset.tfOptimisticSnapshot;
        }
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  LOADING STATES                                          ║
    // ╚══════════════════════════════════════════════════════════╝
    function showLoading(frame, action) {
        frame.classList.add('tf-wire-loading');
        frame.querySelectorAll('[tf-wire\\:loading]').forEach(el => {
            const target = el.getAttribute('tf-wire:target');
            if (target && target !== action) return;
            if (el.hasAttribute('tf-wire:loading.class')) {
                el.classList.add(el.getAttribute('tf-wire:loading.class'));
            } else if (el.hasAttribute('tf-wire:loading.remove')) {
                el.style.display = 'none';
            } else {
                el.style.display = '';
            }
        });
    }

    function hideLoading(frame, action) {
        frame.classList.remove('tf-wire-loading');
        frame.querySelectorAll('[tf-wire\\:loading]').forEach(el => {
            const target = el.getAttribute('tf-wire:target');
            if (target && target !== action) return;
            if (el.hasAttribute('tf-wire:loading.class')) {
                el.classList.remove(el.getAttribute('tf-wire:loading.class'));
            } else if (el.hasAttribute('tf-wire:loading.remove')) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  TURBO STREAM PROCESSOR (with morphing support)          ║
    // ╚══════════════════════════════════════════════════════════╝
    function processStreams(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');

        doc.querySelectorAll('turbo-stream').forEach(stream => {
            const action = stream.getAttribute('action');
            const target = stream.getAttribute('target');
            const el = document.getElementById(target);
            const tpl = stream.querySelector('template');
            const content = tpl ? tpl.innerHTML : '';

            switch (action) {
                case 'replace':
                    if (el) {
                        // Use morphing for replace to preserve focus & state
                        morphElement(el, content);
                    }
                    break;
                case 'update':  if (el) el.innerHTML = content; break;
                case 'remove':  if (el) {
                    el.style.transition = 'all 0.3s ease';
                    el.style.opacity = '0';
                    el.style.transform = 'scale(0.95)';
                    setTimeout(() => el.remove(), 300);
                    break;
                }
                case 'append':  if (el) el.insertAdjacentHTML('beforeend', content); break;
                case 'prepend': if (el) el.insertAdjacentHTML('afterbegin', content); break;
                case 'before':  if (el) el.insertAdjacentHTML('beforebegin', content); break;
                case 'after':   if (el) el.insertAdjacentHTML('afterend', content); break;
            }
        });
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  UTILITY                                                 ║
    // ╚══════════════════════════════════════════════════════════╝
    function findFrame(el) {
        return el.closest('turbo-frame[data-controller="tfwire"]');
    }

    function parseAction(str) {
        const match = str.match(/^(\w+)\((.*)\)$/s);
        if (match) {
            let params;
            try { params = JSON.parse('[' + match[2] + ']'); }
            catch { params = [match[2]]; }
            return { method: match[1], params };
        }
        return { method: str, params: [] };
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  FILE UPLOAD with Progress                               ║
    // ╚══════════════════════════════════════════════════════════╝
    async function handleFileUpload(input, frame) {
        const field = input.getAttribute('tf-wire:model');
        if (!field) return;

        const file = input.files[0];
        if (!file) return;

        // Show preview if available
        const previewEl = frame.querySelector(`[tf-wire\\:upload\\.preview="${field}"] img`);
        if (previewEl && file.type.startsWith('image/')) {
            previewEl.src = URL.createObjectURL(file);
        }

        // Show progress
        const progressEl = frame.querySelector(`[tf-wire\\:upload\\.progress="${field}"]`);
        if (progressEl) progressEl.style.display = '';

        const state = frame.querySelector('input[name="_tf_state"]')?.value || '';
        const id    = frame.querySelector('input[name="_tf_id"]')?.value || '';
        const cls   = frame.querySelector('input[name="_tf_class"]')?.value || '';

        const formData = new FormData();
        formData.append('_tf_state', state);
        formData.append('_tf_id', id);
        formData.append('_tf_class', cls);
        formData.append('_tf_action', 'handleFileUpload');
        formData.append('_tf_params', JSON.stringify([field]));
        formData.append('_tf_file_' + field, file);

        try {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', ENDPOINT);
            xhr.setRequestHeader('Accept', 'text/vnd.turbo-stream.html');
            xhr.setRequestHeader('X-TFWire', '1');

            // Track progress
            xhr.upload.onprogress = (e) => {
                if (!e.lengthComputable) return;
                const pct = Math.round((e.loaded / e.total) * 100);
                const percentEl = frame.querySelector(`[tf-wire\\:upload\\.progress="${field}"] [tf-wire\\:upload\\.percent]`);
                if (percentEl) percentEl.textContent = pct;
                if (progressEl) progressEl.style.setProperty('--tf-upload-progress', pct + '%');
            };

            xhr.onload = () => {
                if (progressEl) progressEl.style.display = 'none';
                if (xhr.status === 200 && xhr.responseText) {
                    window.Turbo?.renderStreamMessage?.(xhr.responseText) ?? processStreams(xhr.responseText);
                }
            };

            xhr.onerror = () => {
                if (progressEl) progressEl.style.display = 'none';
                console.error('[TFWire] Upload failed');
            };

            xhr.send(formData);
        } catch (err) {
            console.error('[TFWire] Upload error:', err);
        }
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  STATE PERSISTENCE                                       ║
    // ╚══════════════════════════════════════════════════════════╝
    function savePersistState(frame) {
        if (!frame.hasAttribute('data-tf-persist')) return;
        const key = 'tfwire:' + frame.id;
        const data = {};

        frame.querySelectorAll('[tf-wire\\:model], [tf-wire\\:model\\.lazy]').forEach(input => {
            const attr = input.getAttributeNames().find(a => a.startsWith('tf-wire:model'));
            const prop = input.getAttribute(attr);
            data[prop] = input.value;
        });

        const driver = frame.getAttribute('data-tf-persist') || 'local';
        const storage = driver === 'session' ? sessionStorage : localStorage;
        storage.setItem(key, JSON.stringify(data));
    }

    function restorePersistState(frame) {
        if (!frame.hasAttribute('data-tf-persist')) return;
        const key = 'tfwire:' + frame.id;
        const driver = frame.getAttribute('data-tf-persist') || 'local';
        const storage = driver === 'session' ? sessionStorage : localStorage;
        const raw = storage.getItem(key);
        if (!raw) return;

        try {
            const data = JSON.parse(raw);
            Object.entries(data).forEach(([prop, val]) => {
                const input = frame.querySelector(`[tf-wire\\:model="${prop}"], [tf-wire\\:model\\.lazy="${prop}"]`);
                if (input) input.value = val;
            });
        } catch {}
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  PREFETCH on Hover                                       ║
    // ╚══════════════════════════════════════════════════════════╝
    document.addEventListener('mouseover', e => {
        const el = e.target.closest('[tf-wire\\:prefetch]');
        if (!el || prefetchCache.has(el)) return;

        prefetchCache.set(el, true);
        const frame = findFrame(el);
        if (!frame) return;

        const { method } = parseAction(el.getAttribute('tf-wire:click') || el.getAttribute('tf-wire:prefetch'));
        if (!method) return;

        // Prefetch in background (silent, no loading)
        const state = frame.querySelector('input[name="_tf_state"]')?.value || '';
        const cls   = frame.querySelector('input[name="_tf_class"]')?.value || '';
        const id    = frame.querySelector('input[name="_tf_id"]')?.value || '';

        const body = new URLSearchParams({ _tf_state: state, _tf_class: cls, _tf_id: id, _tf_action: method });

        fetch(ENDPOINT, {
            method: 'POST', body,
            headers: { 'Accept': 'text/vnd.turbo-stream.html', 'X-TFWire': '1', 'X-TFWire-Prefetch': '1' },
        }).then(r => r.text()).then(html => {
            prefetchCache.set(el, html);
        }).catch(() => {});
    });

    // ╔══════════════════════════════════════════════════════════╗
    // ║  OFFLINE QUEUE & DETECTION                               ║
    // ╚══════════════════════════════════════════════════════════╝
    function showOfflineIndicator() {
        document.querySelectorAll('[tf-wire\\:offline]').forEach(el => el.style.display = '');
        document.querySelectorAll('turbo-frame[data-controller="tfwire"]').forEach(f => f.classList.add('tf-wire-offline'));
    }

    function hideOfflineIndicator() {
        document.querySelectorAll('[tf-wire\\:offline]').forEach(el => el.style.display = 'none');
        document.querySelectorAll('turbo-frame[data-controller="tfwire"]').forEach(f => f.classList.remove('tf-wire-offline'));
    }

    async function replayOfflineQueue() {
        hideOfflineIndicator();
        while (offlineQueue.length > 0) {
            const item = offlineQueue.shift();
            const frame = document.getElementById(item.frameId);
            if (frame) {
                await sendAction(frame, item.action, item.extraData, item.options);
            }
        }
    }

    window.addEventListener('offline', showOfflineIndicator);
    window.addEventListener('online', () => {
        if (offlineQueue.length > 0) replayOfflineQueue();
        else hideOfflineIndicator();
    });

    // ╔══════════════════════════════════════════════════════════╗
    // ║  EVENT DELEGATIONS                                       ║
    // ╚══════════════════════════════════════════════════════════╝

    // ── tf-wire:click ──
    document.addEventListener('click', e => {
        const el = e.target.closest('[tf-wire\\:click]');
        if (!el) return;
        e.preventDefault();
        e.stopPropagation();

        const frame = findFrame(el);
        if (!frame) return;

        // Confirm
        const confirmMsg = el.getAttribute('tf-wire:confirm');
        if (confirmMsg && !window.confirm(confirmMsg)) return;

        // Check prefetch cache
        const cached = prefetchCache.get(el);
        if (cached && typeof cached === 'string') {
            window.Turbo?.renderStreamMessage?.(cached) ?? processStreams(cached);
            prefetchCache.delete(el);
            return;
        }

        const { method, params } = parseAction(el.getAttribute('tf-wire:click'));
        const extra = params.length ? { '_tf_params': JSON.stringify(params) } : {};

        sendAction(frame, method, extra, { optimisticEl: el });
    });

    // ── tf-wire:submit ──
    document.addEventListener('submit', e => {
        const form = e.target.closest('form[tf-wire\\:submit]');
        if (!form) return;
        e.preventDefault();

        const frame = findFrame(form);
        if (!frame) return;

        const action = form.getAttribute('tf-wire:submit');
        const extra = {};
        new FormData(form).forEach((v, k) => {
            if (!k.startsWith('_tf_')) extra['tf_model_' + k] = v;
        });

        sendAction(frame, action, extra);
    });

    // ── tf-wire:model (input) ──
    document.addEventListener('input', e => {
        const el = e.target;
        if (!el.hasAttribute('tf-wire:model')) return;
        if (el.hasAttribute('tf-wire:model.lazy')) return;
        if (el.type === 'file') return;

        const frame = findFrame(el);
        if (!frame) return;

        const debounceAttr = el.getAttributeNames().find(a => a.includes('debounce'));
        let ms = DEBOUNCE_DEFAULT;
        if (debounceAttr) {
            const match = debounceAttr.match(/\.(\d+)ms/);
            if (match) ms = parseInt(match[1], 10);
        }

        const key = frame.id + ':model';
        if (debounceTimers.has(key)) clearTimeout(debounceTimers.get(key));
        debounceTimers.set(key, setTimeout(() => sendAction(frame, null), ms));
    });

    // ── tf-wire:model.lazy (blur/change) ──
    document.addEventListener('change', e => {
        const el = e.target;

        // File upload
        if (el.type === 'file' && el.hasAttribute('tf-wire:model')) {
            const frame = findFrame(el);
            if (frame) handleFileUpload(el, frame);
            return;
        }

        const isLazy = el.hasAttribute('tf-wire:model.lazy')
                    || (el.hasAttribute('tf-wire:model') && (el.type === 'checkbox' || el.type === 'radio'));
        if (!isLazy) return;

        const frame = findFrame(el);
        if (frame) sendAction(frame, null);
    });

    // ── tf-wire:keydown ──
    document.addEventListener('keydown', e => {
        const keyMap = { Enter: 'enter', Escape: 'escape', ArrowUp: 'arrow-up', ArrowDown: 'arrow-down', Tab: 'tab' };
        const keyName = keyMap[e.key] || e.key.toLowerCase();
        const attr = 'tf-wire:keydown.' + keyName;

        const el = e.target.closest(`[${CSS.escape(attr)}]`);
        if (!el) return;

        e.preventDefault();
        const frame = findFrame(el);
        if (!frame) return;

        const { method, params } = parseAction(el.getAttribute(attr));
        const extra = params.length ? { '_tf_params': JSON.stringify(params) } : {};
        sendAction(frame, method, extra);
    });

    // ╔══════════════════════════════════════════════════════════╗
    // ║  POLLING                                                 ║
    // ╚══════════════════════════════════════════════════════════╝
    function initPolling() {
        document.querySelectorAll('turbo-frame[data-tf-poll]').forEach(frame => {
            const ms = parseInt(frame.getAttribute('data-tf-poll'), 10);
            if (!ms || ms < 1000 || pollTimers.has(frame.id)) return;

            const timer = setInterval(() => {
                if (document.hidden || !document.getElementById(frame.id)) {
                    clearInterval(timer);
                    pollTimers.delete(frame.id);
                    return;
                }
                sendAction(frame, null);
            }, ms);

            pollTimers.set(frame.id, timer);
        });
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  DOM OBSERVER                                            ║
    // ╚══════════════════════════════════════════════════════════╝
    const observer = new MutationObserver(mutations => {
        for (const m of mutations) {
            for (const node of m.addedNodes) {
                if (node.nodeType !== 1) continue;

                // Auto-dismiss toasts
                const toasts = node.querySelectorAll?.('[data-auto-dismiss]') || [];
                (node.matches?.('[data-auto-dismiss]') ? [node, ...toasts] : toasts).forEach(toast => {
                    const ms = parseInt(toast.getAttribute('data-auto-dismiss'), 10);
                    if (!ms) return;
                    setTimeout(() => {
                        toast.style.transition = 'all 0.4s ease';
                        toast.style.opacity = '0';
                        toast.style.transform = 'translateX(100%)';
                        setTimeout(() => toast.remove(), 400);
                    }, ms);
                });

                // Init loading
                const loadings = node.querySelectorAll?.('[tf-wire\\:loading]') || [];
                (node.matches?.('[tf-wire\\:loading]') ? [node, ...loadings] : loadings).forEach(el => {
                    if (!el.hasAttribute('tf-wire:loading.remove') && !el.hasAttribute('tf-wire:loading.class')) {
                        el.style.display = 'none';
                    }
                });

                // Polling
                if (node.matches?.('turbo-frame[data-tf-poll]') || node.querySelector?.('turbo-frame[data-tf-poll]')) {
                    initPolling();
                }

                // Persist restore
                if (node.matches?.('turbo-frame[data-tf-persist]')) {
                    restorePersistState(node);
                }

                // Execute inline scripts
                const inlineScripts = node.querySelectorAll?.('script') || [];
                if (inlineScripts.length > 0) {
                    inlineScripts.forEach(script => {
                        const ns = document.createElement('script');
                        ns.textContent = script.textContent;
                        document.body.appendChild(ns);
                        ns.remove();
                    });
                    document.dispatchEvent(new CustomEvent('tfwire:dom-updated'));
                }
            }
        }
    });

    // ╔══════════════════════════════════════════════════════════╗
    // ║  INITIALIZATION                                          ║
    // ╚══════════════════════════════════════════════════════════╝
    function init() {
        // Hide loading & offline indicators
        document.querySelectorAll('[tf-wire\\:loading]').forEach(el => {
            if (!el.hasAttribute('tf-wire:loading.remove') && !el.hasAttribute('tf-wire:loading.class')) {
                el.style.display = 'none';
            }
        });
        if (navigator.onLine) {
            document.querySelectorAll('[tf-wire\\:offline]').forEach(el => el.style.display = 'none');
        }

        // Restore persisted state
        document.querySelectorAll('turbo-frame[data-tf-persist]').forEach(restorePersistState);

        initPolling();
        observer.observe(document.body, { childList: true, subtree: true });

        // tf-wire:init
        document.querySelectorAll('[tf-wire\\:init]').forEach(el => {
            const frame = findFrame(el);
            if (frame) {
                const { method, params } = parseAction(el.getAttribute('tf-wire:init'));
                sendAction(frame, method, params.length ? { '_tf_params': JSON.stringify(params) } : {});
            }
        });
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();

    // ╔══════════════════════════════════════════════════════════╗
    // ║  GLOBAL API                                              ║
    // ╚══════════════════════════════════════════════════════════╝
    window.TFWire = {
        version: VERSION,
        sendAction,
        processStreams,
        morphElement,
        replayOfflineQueue,
        find: id => document.getElementById(id),
        refresh: frameId => {
            const f = document.getElementById(frameId);
            if (f) sendAction(f, null);
        },
        emit: (event, data = {}) => {
            document.dispatchEvent(new CustomEvent('tfwire:' + event, { detail: data }));
        },
        on: (event, callback) => {
            document.addEventListener('tfwire:' + event, e => callback(e.detail));
        },
    };

    console.log(`[TFWire] ⚡ v${VERSION} Engine Ready.`);
})();
