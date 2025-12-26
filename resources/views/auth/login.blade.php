<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kirish</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body {
        min-height: 100vh;
        background: radial-gradient(1200px at 10% 10%, #1e293b, #020617);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #e5e7eb;
    }

    .login-card {
        width: 100%;
        max-width: 420px;
        padding: 40px 35px;
        background: linear-gradient(180deg, #020617, #020617);
        border-radius: 20px;
        border: 1px solid #1e293b;
        box-shadow: 0 30px 80px rgba(0,0,0,.7);
    }

    .login-title {
        font-size: 1.8rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 8px;
        color: #f8fafc;
    }

    .login-sub {
        text-align: center;
        font-size: .95rem;
        color: #94a3b8;
        margin-bottom: 30px;
    }

    .form-label {
        font-size: .9rem;
        color: #cbd5f5;
        margin-bottom: 6px;
    }

    .form-control {
        height: 48px;
        background: #020617;
        border: 1px solid #334155;
        border-radius: 12px;
        color: #f1f5f9;
        padding-left: 14px;
    }

    .form-control::placeholder {
        color: #64748b;
    }

    .form-control:focus {
        background: #020617;
        color: #fff;
        border-color: #6366f1;
        box-shadow: 0 0 0 .2rem rgba(99,102,241,.25);
    }

    .btn-login {
        margin-top: 10px;
        height: 50px;
        border-radius: 14px;
        font-weight: 600;
        background: linear-gradient(135deg, #6366f1, #3b82f6);
        border: none;
    }

    .btn-login:hover {
        filter: brightness(1.1);
    }

    .alert-danger {
        background: rgba(239,68,68,.15);
        border: 1px solid rgba(239,68,68,.4);
        color: #fecaca;
        border-radius: 12px;
        font-size: .9rem;
    }

    .footer-text {
        text-align: center;
        margin-top: 25px;
        font-size: .85rem;
        color: #64748b;
    }
</style>
</head>

<body>

<div class="login-card">

    <div class="login-title">Xush kelibsiz</div>
    <div class="login-sub">Hisobingizga kiring</div>

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   class="form-control"
                   placeholder="you@example.com"
                   required>
        </div>

        <div class="mb-4">
            <label class="form-label">Parol</label>
            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="••••••••"
                   required>
        </div>

        <button type="submit" class="btn btn-login w-100">
            Kirish
        </button>
    </form>

    <div class="footer-text">
        Postix Ai
    </div>

</div>

</body>
</html>
