<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Postix AI'))</title>

    <!-- Bootstrap CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* =========================
   THEME VARIABLES (dark by default)
   Switch by toggling body.light
   ========================= */
        :root {
            --bg: #071427;
            --card: #0f2233;
            --text: #e7f4ff;
            --muted: #9fb7dd;
            --accent: #3b82f6;
            --accent-2: #facc15;
            --muted-2: rgba(255, 255, 255, 0.06);
        }

        /* Light theme overrides */
        body.light {
            --bg: #f6f8fb;
            --card: #ffffff;
            --text: #0b1220;
            --muted: #6b7280;
            --accent: #2563eb;
            --accent-2: #d97706;
            --muted-2: rgba(0, 0, 0, 0.06);
        }

        /* Base */
        html,
        body {
            height: 100%
        }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: var(--bg);
            color: var(--text);
            transition: background .22s ease, color .22s ease;
            -webkit-font-smoothing: antialiased;
        }

        /* Layout */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar: doim ko'rinib turadi va ichida scroll bo'ladi */
        .sidebar {
            width: 260px;
            position: sticky;
            /* <-- bu qator qo'shiladi */
            top: 0;
            /* yuqoridan yopishib turadi */
            height: 100vh;
            /* butun oynani egallaydi */
            overflow: auto;
            /* agar menu uzun bo'lsa — ichida scroll paydo bo'ladi */
            padding: 20px;
            box-sizing: border-box;
            border-right: 1px solid var(--muted-2);
            background: linear-gradient(180deg, var(--card), rgba(0, 0, 0, 0.05));
        }

        /* Mobile uchun sticky olib tashlaymiz (mobilda sidebar yuqoriga aylanadi) */
        @media (max-width: 900px) {
            .sidebar {
                position: static;
                height: auto;
                overflow: visible;
            }
        }


        .sidebar .brand {
            font-weight: 800;
            font-size: 1.1rem;
            margin-bottom: 14px;
            color: var(--text);
        }

        .sidebar .nav-link {
            display: block;
            color: var(--muted);
            padding: 10px 12px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 8px;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
            color: var(--text);
            border-left: 3px solid var(--accent);
        }

        /* Mobile collapse */
        .sidebar-collapsed {
            display: none;
        }

        @media (max-width: 900px) {
            .layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                display: flex;
                gap: 8px;
                align-items: center;
            }

            .sidebar .nav {
                display: flex;
                gap: 8px;
                overflow: auto;
            }

            .sidebar .brand {
                margin-right: auto;
            }
        }

        /* Content */
        .content {
            flex: 1;
            padding: 20px;
            box-sizing: border-box;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        /* Cards */
        .card {
            background: var(--card);
            color: var(--text);
            border: 1px solid var(--muted-2);
            border-radius: 12px;
            box-shadow: 0 8px 26px rgba(2, 6, 23, 0.45);
        }

        /* Small helpers */
        .text-muted {
            color: var(--muted) !important;
        }

        .btn-theme {
            background: transparent;
            border: 1px solid var(--muted-2);
            color: var(--text);
        }

        .lang-btn {
            background: transparent;
            border: 0;
            color: var(--muted);
            padding: 6px 8px;
            border-radius: 6px;
        }

        .profile-avatar {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: linear-gradient(180deg, var(--accent), var(--accent-2));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
        }
    </style>
    <style>
/* ===== Theme variables (keep in sync with your other vars) ===== */
:root{
  --bg:#071427;
  --card:#0f2233;
  --card-2:#122a3f;
  --card-3:#163650;
  --text:#eaf3ff;
  --muted:#9fb7dd;
  --accent:#3b82f6;
  --accent2:#facc15;

  /* peer / inline defaults */
  --peer-bg: rgba(255,255,255,0.02);
  --peer-border: rgba(255,255,255,0.04);
  --inline-bg: rgba(255,255,255,0.02);
  --inline-border: rgba(255,255,255,0.04);
  --chip-bg: rgba(255,255,255,0.02);
}

/* LIGHT THEME overrides */
body.light{
  --bg: #f4f6fb;
  --card: #ffffff;
  --card-2: #f8fafc;
  --card-3: #ffffff;
  --text: #0b1220;
  --muted: #6b7280;
  --accent: #2563eb;
  --accent2: #d97706;

  --peer-bg: rgba(11,17,32,0.03);
  --peer-border: rgba(11,17,32,0.06);
  --inline-bg: rgba(11,17,32,0.03);
  --inline-border: rgba(11,17,32,0.06);
  --chip-bg: rgba(11,17,32,0.03);
}

/* ===== Elements that must adapt to theme ===== */
.message-group {
  background: var(--card-2) !important;
  border: 1px solid rgba(255,255,255,0.04);
  color: var(--text);
}

/* message text panel */
.message-text {
  background: var(--card-3) !important;
  color: var(--text) !important;
  border-left: 4px solid var(--accent);
}

/* peer rows (was inline rgba) */
.peer-row {
  background: var(--peer-bg) !important;
  border: 1px solid var(--peer-border) !important;
  color: var(--text) !important;
}

/* override any inline styles that used rgba(...) to a theme-aware variable */
*[style*="background:rgba(255,255,255,0.02)"],
*[style*="background: rgba(255,255,255,0.02)"] {
  background: var(--inline-bg) !important;
  /* try to preserve border if present */
  border-color: var(--inline-border) !important;
  color: var(--text) !important;
}

/* also override other tiny inline backgrounds if present */
*[style*="background:rgba(255,255,255,0.01)"],
*[style*="background: rgba(255,255,255,0.01)"] {
  background: var(--inline-bg) !important;
  border-color: var(--inline-border) !important;
  color: var(--text) !important;
}

/* status badges - keep color cues but ensure readable in light mode */
.status-badge { color: #062; } /* fallback */

.status-badge.status-sent { background: #bbf7d0; color: #064e3b; }
.status-badge.status-failed { background: #fecaca; color: #7f1d1d; }
.status-badge.status-canceled { background: #e9d5ff; color: #5b21b6; }
.status-badge.status-scheduled { background: #fef3c7; color: #92400e; }
.status-badge.status-pending { background: #dbeafe; color: #1e3a8a; }

/* small chips inside peer rows */
.status-chip {
  background: var(--chip-bg) !important;
  color: var(--text) !important;
  border-radius: 8px;
  padding: 4px 8px;
  font-weight:700;
}

/* ensure text-muted adapts */
.text-muted.small { color: var(--muted) !important; }

/* scrollbar cosmetic */
.peer-row::-webkit-scrollbar, .card::-webkit-scrollbar { height:6px; width:6px; }
.peer-row::-webkit-scrollbar-thumb, .card::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius:6px; }

/* safety: override any other inline color that hides text */
*[style*="color:var(--text-disabled)"] { color: var(--text) !important; }

</style>


</head>

<body>
    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="brand">{{ config('app.name', 'Postix AI') }}</div>

            <nav class="nav flex-column mb-3">
                <a href="{{ route('departments.dashboard', $department) }}"
                    class="nav-link {{ request()->routeIs('departments.dashboard') ? 'active' : '' }}">
                    🏠 {{ __('messages.admin.dashboard') }}
                </a>

                <a href="{{ route('departments.users', $department) }}"
                    class="nav-link {{ request()->routeIs('departments.users') ? 'active' : '' }}">
                    👤 {{ __('messages.admin.users') }}
                </a>

                <a href="{{ route('departments.operations', $department) }}"
                    class="nav-link {{ request()->routeIs('departments.operations') ? 'active' : '' }}">
                    📊 {{ __('messages.admin.operations') }}
                </a>

                {{-- <a href="{{ route('settings.index') ?? '#' }}" class="nav-link @if (request()->routeIs('settings.*')) active @endif">⚙️ {{ __('layout.menu.settings') }}</a> --}}
            </nav>


        </aside>

        <!-- MAIN CONTENT -->
        <main class="content">
            <div class="topbar">
                <div>
                    <h4 class="mb-0">@yield('page-title', __('messages.layout.page_title'))</h4>
                    <div class="text-muted small">@yield('page-subtitle')</div>
                </div>

                <div class="d-flex align-items-center gap-2">

                    {{-- Theme toggle --}}
                    <button id="themeToggleBtn" class="btn btn-theme btn-sm"
                        title="{{ __('messages.layout.toggle_theme') }}">☀️</button>

                    {{-- Language dropdown (mirror) --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="langMenuBtn"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            {{ strtoupper(app()->getLocale()) }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langMenuBtn">
                            <li>
                                <a class="dropdown-item" href="{{ url('/lang/uz') }}">
                                    Oʻzbekcha
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ url('/lang/en') }}">
                                    English
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ url('/lang/ru') }}">
                                    Русский
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ url('/lang/ko') }}">
                                    한국어
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ url('/lang/zh') }}">
                                    中文
                                </a>
                            </li>


                        </ul>

                    </div>

                    {{-- Profile dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2"
                            type="button" id="profileMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span
                                class="profile-avatar">{{ strtoupper(substr(auth()->user()->name ?? (auth()->user()->username ?? 'U'), 0, 1)) }}</span>
                            <span
                                class="d-none d-md-inline">{{ auth()->user()->name ?? (auth()->user()->username ?? __('messages.layout.profile')) }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileMenuBtn">
                            <li><a class="dropdown-item"
                                    href="{{ route('profile') ?? '#' }}">{{ __('messages.layout.profile') }}</a></li>
                            <li><a class="dropdown-item" href="">{{ __('messages.layout.settings') }}</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="margin:0;">
                                    @csrf
                                    <button type="submit"
                                        class="dropdown-item text-danger">{{ __('messages.layout.logout') }}</button>
                                </form>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

            {{-- Content area --}}
            <div class="container-fluid p-0">
                @yield('content')
            </div>

        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        /*
                  Theme toggle: stores 'light'|'dark' in localStorage under key 'app_theme'
                  Usage: body.classList.toggle('light', true) -> light theme
                */
        (function() {
            const THEME_KEY = 'app_theme';
            const body = document.body;
            const btn = document.getElementById('themeToggleBtn');

            function applyTheme(theme) {
                body.classList.toggle('light', theme === 'light');
                // button label/icon/text
                if (btn) btn.textContent = theme === 'light' ? '🌙' : '☀️';
            }

            // get saved or detect
            const saved = localStorage.getItem(THEME_KEY);
            let theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ?
                'light' : 'dark');

            applyTheme(theme);

            if (btn) {
                btn.addEventListener('click', function() {
                    theme = body.classList.contains('light') ? 'dark' : 'light';
                    localStorage.setItem(THEME_KEY, theme);
                    applyTheme(theme);
                });
            }
        })();
    </script>

</body>

</html>
