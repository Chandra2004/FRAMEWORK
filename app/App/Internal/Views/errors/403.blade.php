<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Forbidden</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #0a0a0c;
            color: #f4f4f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.04) 0%, transparent 70%);
            pointer-events: none;
        }

        .container {
            text-align: center;
            position: relative;
            z-index: 1;
            max-width: 500px;
            padding: 40px;
        }

        .error-code {
            font-size: 140px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -8px;
        }

        .error-code span {
            background: linear-gradient(135deg, #F59E0B, #FBBF24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 16px;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            color: #f4f4f5;
            margin: 16px 0 8px;
        }

        .desc {
            color: #71717a;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #F59E0B, #D97706);
            color: #18181b;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.25);
        }

        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.35);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #18181b;
            border: 1px solid #27272a;
            color: #a1a1aa;
        }

        .btn-secondary:hover {
            background: #27272a;
            color: #f4f4f5;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon">
            <svg width="28" height="28" fill="none" stroke="#F59E0B" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v.01M12 9v2m-7 4h14l-7-12-7 12z"></path>
            </svg>
        </div>
        <div class="error-code"><span>403</span></div>
        <h1 class="title">Access Denied</h1>
        <p class="desc">Anda tidak memiliki izin untuk mengakses halaman ini. Jika Anda yakin ini adalah kesalahan,
            hubungi administrator.</p>
        <div class="actions">
            <a href="{{ url('/') }}" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                    </path>
                </svg>
                Kembali ke Home
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Go Back
            </a>
        </div>
    </div>
</body>

</html>
