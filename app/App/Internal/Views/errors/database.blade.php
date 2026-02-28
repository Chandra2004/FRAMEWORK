<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Error — The Framework</title>
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
            background: linear-gradient(180deg, #130e0a, #131316);
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
            background: linear-gradient(90deg, #F97316, #FB923C, #F97316);
        }

        .header .glow {
            position: absolute;
            top: -120px;
            left: -60px;
            width: 500px;
            height: 500px;
            background: rgba(249, 115, 22, 0.06);
            filter: blur(150px);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
            padding: 28px 32px;
        }

        .db-icon {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .db-icon .icon-box {
            width: 28px;
            height: 28px;
            background: rgba(249, 115, 22, 0.15);
            border: 1px solid rgba(249, 115, 22, 0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .db-icon span {
            color: #FB923C;
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

        .db-badge {
            background: rgba(249, 115, 22, 0.15);
            color: #FB923C;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid rgba(249, 115, 22, 0.2);
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

        .sql-box {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.7;
            background: #1a1a1e;
            padding: 20px;
            color: #d4d4d8;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .sql-box .keyword {
            color: #60A5FA;
            font-weight: 700;
        }

        .env-table {
            width: 100%;
            border-collapse: collapse;
        }

        .env-table th {
            text-align: left;
            padding: 8px 20px;
            font-size: 11px;
            font-weight: 600;
            color: #52525b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #1a1a1e;
            border-bottom: 1px solid #27272a;
        }

        .env-table td {
            padding: 8px 20px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            border-bottom: 1px solid #1e1e22;
        }

        .env-table td:first-child {
            color: #FB923C;
            font-weight: 500;
            width: 180px;
        }

        .env-table td:last-child {
            color: #d4d4d8;
        }

        .env-table tr:last-child td {
            border-bottom: none;
        }

        .env-hidden {
            color: #3f3f46;
            font-style: italic;
        }

        .hint-box {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.06), rgba(249, 115, 22, 0.02));
            border: 1px solid rgba(249, 115, 22, 0.15);
            border-radius: 12px;
            padding: 20px 24px;
        }

        .hint-box h3 {
            color: #FB923C;
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
            padding: 5px 0 5px 20px;
            position: relative;
            line-height: 1.5;
        }

        .hint-box li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: #FB923C;
        }

        .hint-box code {
            color: #FB923C;
            background: #1e1e22;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
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
    </style>
</head>

<body>
    <div class="header">
        <div class="glow"></div>
        <div class="header-content">
            <div class="db-icon">
                <div class="icon-box">🗄️</div>
                <span>{{ $error_type ?? 'Database Error' }}</span>
            </div>
            <h1>{{ $message }}</h1>
            <div class="header-meta">
                <span class="db-badge">{{ $error_type ?? 'DB ERROR' }}</span>
                <span class="meta-text">{{ $request_info['uri'] ?? '/' }}</span>
                <span style="color:#3f3f46">•</span>
                <span class="meta-text">PHP {{ $environment['php_version'] ?? PHP_VERSION }}</span>
            </div>
        </div>
    </div>

    <div class="main-grid">
        <!-- SQL Query (if available) -->
        @if (!empty($sql))
            <div class="card">
                <div class="card-header">
                    <svg width="14" height="14" fill="none" stroke="#FB923C" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4">
                        </path>
                    </svg>
                    <h3>SQL Query</h3>
                </div>
                <div class="sql-box"><?php
                $sqlDisplay = $sql ?? '';
                $keywords = ['SELECT', 'FROM', 'WHERE', 'INSERT', 'INTO', 'UPDATE', 'DELETE', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'ON', 'SET', 'VALUES', 'ORDER BY', 'GROUP BY', 'LIMIT', 'AND', 'OR', 'NOT', 'NULL', 'IS', 'IN', 'LIKE', 'CREATE', 'TABLE', 'ALTER', 'DROP', 'IF', 'EXISTS', 'PRIMARY', 'KEY', 'INDEX', 'UNIQUE', 'FOREIGN', 'REFERENCES', 'CASCADE', 'HAVING', 'AS', 'DISTINCT', 'COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'BETWEEN'];
                $escaped = htmlspecialchars($sqlDisplay);
                foreach ($keywords as $kw) {
                    $escaped = preg_replace('/\b(' . $kw . ')\b/i', '<span class="keyword">$1</span>', $escaped);
                }
                echo $escaped;
                ?></div>

                @if (!empty($bindings))
                    <div style="padding:12px 20px;font-size:12px;border-top:1px solid #27272a;background:#1a1a1e;">
                        <span style="color:#52525b;font-weight:600;">Bindings:</span>
                        <span
                            style="color:#d4d4d8;font-family:'JetBrains Mono',monospace;">{{ json_encode($bindings) }}</span>
                    </div>
                @endif
            </div>
        @endif

        <!-- Connection Config -->
        <div class="card">
            <div class="card-header">
                <h3>Database Configuration</h3>
            </div>
            <table class="env-table">
                <thead>
                    <tr>
                        <th>Variable</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $envVals = $env_values ?? [];
                        $dbVars = [
                            'DB_CONNECTION' => $envVals['DB_CONNECTION'] ?? ($_ENV['DB_CONNECTION'] ?? 'mysql'),
                            'DB_HOST' => $envVals['DB_HOST'] ?? ($_ENV['DB_HOST'] ?? 'not set'),
                            'DB_PORT' => $envVals['DB_PORT'] ?? ($_ENV['DB_PORT'] ?? 'not set'),
                            'DB_NAME' => $envVals['DB_NAME'] ?? ($_ENV['DB_NAME'] ?? 'not set'),
                            'DB_USER' => $envVals['DB_USER'] ?? ($_ENV['DB_USER'] ?? 'not set'),
                            'DB_PASS' => !empty($envVals['DB_PASS'] ?? ($_ENV['DB_PASS'] ?? ''))
                                ? '••••••••'
                                : 'not set',
                            'DB_TIMEZONE' => $envVals['DB_TIMEZONE'] ?? ($_ENV['DB_TIMEZONE'] ?? 'not set'),
                        ];
                    @endphp
                    @foreach ($dbVars as $key => $val)
                        <tr>
                            <td>{{ $key }}</td>
                            <td>
                                @if ($val === 'not set' || $val === '••••••••')
                                    <span
                                        class="{{ $val === 'not set' ? 'env-hidden' : '' }}">{{ $val }}</span>
                                @else
                                    {{ $val }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Troubleshooting -->
        <div class="hint-box">
            <h3>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                    </path>
                </svg>
                Solutions
            </h3>
            <ul>
                @if (isset($error_type) && $error_type === 'Connection Error')
                    <li>Pastikan MySQL/MariaDB server sedang <strong>berjalan</strong></li>
                    <li>Cek <code>DB_HOST</code> dan <code>DB_PORT</code> di file <code>.env</code></li>
                    <li>Pastikan user <code>DB_USER</code> memiliki akses ke database <code>DB_NAME</code></li>
                @elseif(isset($error_type) && $error_type === 'Duplicate Entry')
                    <li>Data yang Anda masukkan sudah ada di database (unique constraint violation)</li>
                    <li>Cek kolom <strong>UNIQUE</strong> di tabel terkait</li>
                @elseif(isset($error_type) && $error_type === 'SQL Syntax Error')
                    <li>Periksa query SQL di atas — kemungkinan ada <strong>typo</strong> atau <strong>missing
                            column</strong></li>
                    <li>Pastikan nama tabel dan kolom benar</li>
                    <li>Jalankan <code>php artisan migrate:status</code> untuk cek status migrasi</li>
                @else
                    <li>Pastikan database server berjalan: <code>mysql -u root -p</code></li>
                    <li>Buat database jika belum: <code>CREATE DATABASE db_name;</code></li>
                    <li>Pastikan <code>.env</code> sudah benar, lalu jalankan <code>php artisan migrate</code></li>
                    <li>Cek log: <code>storage/logs/framework-{{ date('Y-m-d') }}.log</code></li>
                @endif
            </ul>
        </div>

        <!-- Request Info -->
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
