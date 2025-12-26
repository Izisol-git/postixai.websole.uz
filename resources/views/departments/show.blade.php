<!DOCTYPE html>
<html lang="uz">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>{{ $department->name ?? 'Department' }} — Batafsil — Postix Ai</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg: #071427;
            --card: #0f2233;
            --muted: #9fb7dd;
            --text: #e7f4ff;
            --accent: #3b82f6;
            --yellow: #facc15;
            --danger: #ef4444;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
            padding: 18px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 12px;
        }

        .title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .breadcrumbs {
            color: var(--muted);
            font-size: 0.95rem;
        }

        /* Card */
        .card {
            background: var(--card);
            border-radius: 12px;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.6);
            margin-bottom: 14px;
        }

        .card h3 {
            color: var(--yellow);
            margin-top: 0;
            margin-bottom: 10px;
        }

        /* Stats */
        .stats {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .stat {
            background: #0a1b3c;
            border-radius: 10px;
            padding: 12px;
            flex: 1 1 160px;
            text-align: center;
        }

        .stat h4 {
            margin: 0;
            font-size: 1.3rem;
            color: var(--accent);
        }

        .stat p {
            margin: 6px 0 0;
            font-size: 0.95rem;
            color: var(--muted);
        }

        /* Compact users list */
        .users-compact {
            margin-bottom: 14px;
        }

        .user-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 10px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .user-line .left {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .user-name {
            font-weight: 700;
            color: var(--text);
        }

        .user-telegram {
            color: var(--muted);
        }

        /* Phone dropdown */
        .form-select-sm {
            background: #071827;
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 7px;
            padding: 6px 10px;
        }

        /* MessageGroup card */
        .mg-card {
            background: rgba(255, 255, 255, 0.02);
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .search-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .search-form input {
            flex: 1;
        }

        .mg-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .mg-title {
            font-weight: 800;
            color: var(--text);
        }

        .mg-meta {
            color: var(--muted);
            font-size: 0.9rem;
        }

        /* Buttons */
        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid rgba(255, 255, 255, 0.04);
            padding: 6px 10px;
            border-radius: 8px;
        }

        .btn-refresh {
            background: var(--accent);
            color: white;
            border-radius: 8px;
            padding: 6px 10px;
            border: none;
        }

        .btn-cancel {
            background: var(--danger);
            color: white;
            border-radius: 8px;
            padding: 6px 10px;
            border: none;
        }

        /* text importance */
        .normal {
            color: var(--text);
        }

        .important {
            color: var(--yellow);
            font-weight: 700;
        }

        /* badges & small */
        .badge {
            background: #0e2342;
            color: var(--text);
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .small-note {
            color: var(--muted);
            font-size: 0.9rem;
        }

        /* messages */
        .msg {
            background: rgba(255, 255, 255, 0.01);
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 8px;
            color: #e6f2ff;
        }

        .meta-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        /* totals line */
        .totals-line {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
            color: var(--muted);
        }

        /* responsive */
        @media (max-width:900px) {
            .mg-header {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .totals-line {
                justify-content: flex-start;
                margin-top: 8px;
            }
        }

        /* Pagination Style */
        .pagination {
            display: flex;
            list-style: none;
            gap: 8px;
            padding: 0;
            justify-content: center;
            margin-top: 20px;
        }

        .page-item .page-link {
            background: var(--card);
            color: var(--muted);
            border: 1px solid rgba(255, 255, 255, 0.04);
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.95rem;
        }

        .page-item.active .page-link {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .page-item.disabled .page-link {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .page-item .page-link:hover:not(.active, .disabled) {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- Top -->
        <div class="topbar">
            <div class="title">
                <span style="font-weight:800; color:var(--yellow)">POSTIX AI</span>
                <span class="breadcrumbs"> / <a href="{{ route('departments.index') }}"
                        style="color:var(--muted); text-decoration:none;">Departments</a> → <span
                        style="color:var(--text)">{{ $department->name }}</span></span>
            </div>

            <div>
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button class="btn-ghost" type="submit" style="background:#ef4444; color:white;">Logout</button>
                </form>
            </div>
        </div>

        <!-- Department header -->
        <div class="card">
            <h3>{{ $department->name }} — Batafsil</h3>

            <div class="stats">
                <div class="stat">
                    <h4>{{ $usersCount ?? 0 }}</h4>
                    <p>Foydalanuvchilar</p>
                </div>
                <div class="stat">
                    <h4>{{ $activePhonesCount ?? 0 }}</h4>
                    <p>Aktiv telefonlar</p>
                </div>
                <div class="stat">
                    <h4>{{ $messageGroupsTotal ?? 0 }}</h4>
                    <p>Operatsiya</p>
                </div>
                <div class="stat">
                    <h4>{{ $telegramMessagesTotal ?? 0 }}</h4>
                    <p>Habarlar soni</p>
                </div>
            </div>
        </div>

        <!-- Users List with Delete, Show, Ban User, and Phone Ban Checkbox -->
        <div class="users-compact">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <h5 style="color:var(--yellow); margin-bottom:8px; cursor:pointer;" onclick="toggleUsersList()">
                    Foydalanuvchilar ▾
                </h5>
                <!-- Toast notifications -->
                <div id="toast-container" style="position:fixed; top:20px; right:20px; z-index:9999;"></div>

                <!-- CREATE USER BUTTON -->
                <a href="{{ route('users.create') }}" class="btn btn-sm"
                    style="background:#22c55e; color:#fff; padding:5px 14px; font-size:12px; border-radius:8px; text-decoration:none;">
                    + Add User
                </a>
            </div>

            <div id="usersList" style="display:none; margin-top:6px;">
                @foreach ($users as $user)
                    @php
                        $userBanned = $user->ban && $user->ban->active;
                        $activePhone = $user->phones->firstWhere('is_active', 1);
                        $phoneBanned = $activePhone && $activePhone->ban && $activePhone->ban->active;
                    @endphp

                    <div class="user-line" data-user-id="{{ $user->id }}">
                        <div class="left" style="display:flex; align-items:center; gap:12px;">
                            <div class="user-name">{{ $user->name ?? '—' }}</div>
                            <div class="user-telegram">({{ $user->telegram_id ?? '—' }})</div>
                        </div>

                        <div style="min-width:360px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <select class="form-select-sm phone-select" data-user-id="{{ $user->id }}">
                                @foreach ($user->phones as $phone)
                                    <option value="{{ $phone->id }}" {{ $phone->is_active ? 'selected' : '' }}>
                                        {{ $phone->phone }}
                                    </option>
                                @endforeach
                            </select>

                            <a href="{{ route('telegram.login', ['user_id' => $user->id]) }}" class="btn btn-sm"
                                style="background:#4ade80; color:white; padding:5px 12px; font-size:11px; border-radius:6px; text-decoration:none;">
                                Add Phone
                            </a>


                            <a href="/users/{{ $user->id }}" class="btn btn-sm"
                                style="background:#3b82f6; color:#fff; padding:4px 8px; font-size:11px; border-radius:6px; text-decoration:none;">
                                Show
                            </a>

                            <button type="button" class="btn btn-sm ban-toggle-btn"
                                style="background: {{ $userBanned ? '#ef4444' : '#6b7280' }}; color:#fff; padding:5px 12px; font-size:11px; border-radius:6px; border:none;"
                                data-type="user" data-id="{{ $user->id }}" onclick="toggleBan(this)">
                                {{ $userBanned ? 'User Banned' : 'Ban User' }}
                            </button>

                            <button type="button" class="btn btn-sm"
                                style="background:#ef4444; color:#fff; padding:4px 8px; font-size:11px; border-radius:6px; border:none;"
                                onclick="deleteUser({{ $user->id }})">
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>


        <!-- MessageGroups list (paginated) -->
        <div style="margin-top:18px;">
            <h5 style="color:var(--yellow); margin-bottom:6px;">Operatsiyalar</h5>
            <form method="GET" action="{{ route('departments.show', $department->id) }}" class="search-form mb-3"
                role="search">
                <input class="form-control" type="search" name="q" value="{{ $search ?? '' }}"
                    placeholder="Message text bo'yicha qidirish..."
                    style="background: #ffffff; border: 1px solid rgba(0,0,0,0.08); color: #071427; border-radius: 8px;">
                <button class="btn btn-primary" type="submit">Search</button>
            </form>


            @foreach ($messageGroups as $group)
                @php
                    $gid = $group->id;
                    $stat = $textStats->get($gid);
                    $peers = $peerStatusByGroup[$gid] ?? [];
                    $total = $groupTotals[$gid] ?? [];

                    $labels = [
                        'sent' => ['label' => 'sent', 'color' => '#4ade80'],
                        'canceled' => ['label' => 'canceled', 'color' => '#f87171'],
                        'scheduled' => ['label' => 'scheduled', 'color' => '#facc15'],
                        'failed' => ['label' => 'failed', 'color' => '#ef4444'],
                    ];
                @endphp

                <div class="mg-card">
                    <div class="mg-header">
                        <div>
                            <div class="mg-title">Operatsiya #{{ $gid }}</div>
                            <div class="mg-meta">
                                {{ optional($group->phone->user)->name ?? '—' }}
                                ({{ optional($group->phone)->phone ?? '—' }})
                            </div>
                        </div>

                        <div style="display:flex; gap:8px;">
                            <a href="#" class="btn-refresh"
                                onclick="return onRefresh(event, {{ $gid }})">Refresh</a>
                            <a href="#" class="btn-cancel"
                                onclick="return onCancel(event, {{ $gid }})">Cancel</a>
                        </div>
                    </div>

                    <hr style="border-color: rgba(255,255,255,0.04); margin:8px 0;">

                    <div
                        style="background:rgba(255,255,255,0.05); padding:10px; border-radius:8px; margin-top:6px; word-break:break-word;">
                        <strong style="color:var(--yellow);">Text:</strong>
                        <span style="font-weight:600; color:var(--text);">
                            {{ $stat->sample_text ?? '—' }}
                        </span>
                    </div>

                    <div style="margin-top:6px;">
                        <!-- Search + controls -->
                        <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                            <input type="search" class="peer-search" placeholder="Search peers..."
                                style="flex:1; padding:6px 10px; border-radius:6px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.04); color:var(--text);">
                            <button type="button" class="peer-filter-failed btn-sm"
                                title="Show only peers with failed"
                                style="padding:6px 8px; border-radius:6px; background:#ef4444; color:#fff; border:none;">
                                Failed only
                            </button>
                            <button type="button" class="peer-clear btn-sm" title="Clear"
                                style="padding:6px 8px; border-radius:6px; background:#64748b; color:#fff; border:none;">
                                Clear
                            </button>
                        </div>

                        <!-- Compact scrollable peer list (fixed height) -->
                        <div class="peer-list" style="max-height:220px; overflow:auto; padding-right:6px;">
                            @foreach ($peers as $peer => $statuses)
                                @php $peerTotal = array_sum($statuses); @endphp

                                <div class="peer-row" data-peer="{{ $peer }}"
                                    data-sent="{{ $statuses['sent'] ?? 0 }}"
                                    data-failed="{{ $statuses['failed'] ?? 0 }}"
                                    data-canceled="{{ $statuses['canceled'] ?? 0 }}"
                                    data-scheduled="{{ $statuses['scheduled'] ?? 0 }}"
                                    style="display:flex; justify-content:space-between; align-items:center; padding:6px 8px; border-radius:6px; margin-bottom:6px; background:rgba(255,255,255,0.02);">

                                    <div style="display:flex; gap:8px; align-items:center; min-width:0;">
                                        <strong
                                            style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;">{{ $peer }}</strong>
                                        <span class="small-note" style="opacity:0.7;">total:
                                            {{ $peerTotal }}</span>
                                    </div>

                                    <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                                        @foreach ($labels as $key => $info)
                                            @php $count = $statuses[$key] ?? 0; @endphp
                                            @if ($count > 0)
                                                <span title="{{ ucfirst($info['label']) }}"
                                                    style="
                    display:flex;
                    align-items:center;
                    gap:4px;
                    background:{{ $info['color'] }}22;
                    color:{{ $info['color'] }};
                    padding:3px 7px;
                    border-radius:6px;
                    font-size:11px;
                    font-weight:700;
                    white-space:nowrap;
                ">
                                                    {{-- Icon --}}
                                                    @if ($key === 'sent')
                                                        ✓
                                                    @elseif ($key === 'failed')
                                                        ✕
                                                    @elseif ($key === 'canceled')
                                                        ⦸
                                                    @elseif ($key === 'scheduled')
                                                        ⏳
                                                    @endif

                                                    {{-- Label + count --}}
                                                    <span style="opacity:.85;">{{ $info['label'] }}</span>
                                                    <span>{{ $count }}</span>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div style="margin-top:6px; font-weight:700;">
                        total —
                        @foreach ($labels as $key => $info)
                            @if (($total[$key] ?? 0) > 0)
                                <span style="color:{{ $info['color'] }}; margin-right:8px;">
                                    {{ $total[$key] }} {{ $info['label'] }}
                                </span>
                            @endif
                        @endforeach
                        <span class="small-note"> All: {{ array_sum($total) }}</span>
                    </div>
                </div>
            @endforeach

            <div class="mt-3">
                {{ $messageGroups->withQueryString()->links('pagination::bootstrap-5') }}
            </div>

        </div>

    </div>
    <div id="toast-container" style="
    position:fixed;
    top:20px;
    right:20px;
    z-index:9999;
"></div>
    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast(@json(session('success')), 'success');
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast(@json(session('error')), 'error');
            });
        </script>
    @endif


    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        function toggleBan(button) {
            const isCurrentlyBanned = button.textContent.includes('Banned');
            const type = button.getAttribute('data-type');
            const id = button.getAttribute('data-id');

            if (isCurrentlyBanned) {
                button.textContent = 'Ban User';
                button.style.background = '#6b7280';
            } else {
                button.textContent = 'User Banned';
                button.style.background = '#ef4444';
            }

            const userLine = button.closest('.user-line');
            const phoneCheckbox = userLine.querySelector('.phone-ban-checkbox');
            if (!isCurrentlyBanned) {
                if (phoneCheckbox) {
                    phoneCheckbox.disabled = true;
                }
            } else {
                if (phoneCheckbox) {
                    phoneCheckbox.disabled = false;
                }
            }

            fetch('/admin/toggle-ban', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        type: type,
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Xatolik: ' + (data.message || 'Noma\'lum xato'));
                        if (isCurrentlyBanned) {
                            button.textContent = 'User Banned';
                            button.style.background = '#ef4444';
                        } else {
                            button.textContent = 'Ban User';
                            button.style.background = '#6b7280';
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (isCurrentlyBanned) {
                        button.textContent = 'User Banned';
                        button.style.background = '#ef4444';
                    } else {
                        button.textContent = 'Ban User';
                        button.style.background = '#6b7280';
                    }
                });
        }
        (function ensureToastContainer() {
            if (!document.getElementById('toast-container')) {
                const c = document.createElement('div');
                c.id = 'toast-container';
                c.style.position = 'fixed';
                c.style.top = '20px';
                c.style.right = '20px';
                c.style.zIndex = '9999';
                document.body.appendChild(c);
            }
        })();

        function toggleBanCheckbox(checkbox) {
            const type = checkbox.getAttribute('data-type');
            const id = checkbox.getAttribute('data-id');
            const isChecked = checkbox.checked;

            fetch('/admin/toggle-ban', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        type: type,
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Xatolik: ' + (data.message || 'Noma\'lum xato'));
                        checkbox.checked = !isChecked;
                    } else {
                        const select = checkbox.closest('.user-line').querySelector('.phone-select');
                        const selectedOption = select.options[select.selectedIndex];
                        selectedOption.setAttribute('data-phone-banned', isChecked ? '1' : '0');
                    }
                })
                .catch(err => {
                    console.error(err);
                    checkbox.checked = !isChecked;
                });
        }

        document.querySelectorAll('.phone-select').forEach(select => {
            select.addEventListener('change', function() {
                const userLine = this.closest('.user-line');
                const phoneCheckbox = userLine.querySelector('.phone-ban-checkbox');
                const userBtn = userLine.querySelector('.ban-toggle-btn[data-type="user"]');
                const selectedOption = this.options[this.selectedIndex];

                const phoneId = selectedOption.value;
                const isPhoneBanned = selectedOption.getAttribute('data-phone-banned') === '1';
                const userBanned = userBtn.textContent.includes('Banned');

                phoneCheckbox.setAttribute('data-id', phoneId);
                phoneCheckbox.checked = isPhoneBanned;

                if (userBanned) {
                    phoneCheckbox.disabled = true;
                } else {
                    phoneCheckbox.disabled = false;
                }
            });
        });

        function deleteUser(userId) {
            showConfirmModal('Bu foydalanuvchini o‘chirib tashlamoqchimisiz?', () => {
                const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrf = tokenMeta ? tokenMeta.getAttribute('content') : null;

                fetch(`/users/${userId}`, {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            ...(csrf ? {
                                'X-CSRF-TOKEN': csrf
                            } : {})
                        }
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type') || '';
                        if (!response.ok) {
                            const text = contentType.includes('application/json') ? await response.json()
                                .catch(() => null) : await response.text().catch(() => null);
                            const msg = (text && text.message) ? text.message : (typeof text === 'string' &&
                                text.length ? text : `Server error ${response.status}`);
                            throw new Error(msg);
                        }
                        if (contentType.includes('application/json')) return response.json();
                        const txt = await response.text().catch(() => null);
                        return {
                            success: true,
                            message: txt || 'OK'
                        };
                    })
                    .then(data => {
                        if (data && data.success) {
                            // remove DOM safely
                            const userLine = document.querySelector(`.user-line[data-user-id="${userId}"]`);
                            if (userLine) userLine.remove();
                            // toast: deletion info in corner (red)
                            showToast(data.message || 'Foydalanuvchi o‘chirildi', 'error');
                        } else {
                            showToast((data && data.message) || 'Xatolik yuz berdi', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showToast(err.message || 'Server bilan bog‘lanishda xato', 'error');
                    });
            });
        }

        function confirmDelete(message, onConfirm) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.style.padding = '12px 18px';
            toast.style.borderRadius = '8px';
            toast.style.marginTop = '8px';
            toast.style.color = '#fff';
            toast.style.background = '#facc15';
            toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
            toast.style.maxWidth = '300px';
            toast.style.opacity = '0';
            toast.style.transition = 'all 0.3s ease';
            toast.innerHTML = `
        ${message}
        <div style="margin-top:8px; text-align:right;">
            <button id="confirm-yes" style="margin-right:6px; background:#ef4444; color:white; border:none; padding:4px 10px; border-radius:6px;">Yes</button>
            <button id="confirm-no" style="background:#64748b; color:white; border:none; padding:4px 10px; border-radius:6px;">No</button>
        </div>
        `;
            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
            });

            toast.querySelector('#confirm-yes').addEventListener('click', () => {
                onConfirm();
                toast.remove();
            });
            toast.querySelector('#confirm-no').addEventListener('click', () => toast.remove());

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 8000); // 8s auto hide
        }


        function toggleUsersList() {
            const list = document.getElementById('usersList');
            list.style.display = (list.style.display === 'none' ? 'block' : 'none');
        }

        function onRefresh(e, id) {
            e.preventDefault();
            console.log('Refresh funksiyasi: Group #' + id);
            return false;
        }

        function onCancel(e, id) {
            e.preventDefault();
            if (!confirm('Bu operatsiyani bekor qilmoqchimisiz?')) return false;
            console.log('Cancel funksiyasi: Group #' + id);
            return false;
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return console.warn('Toast container yo‘q');

            const toast = document.createElement('div');
            toast.innerHTML = message; // HTML allowed
            toast.style.padding = '10px 14px';
            toast.style.borderRadius = '8px';
            toast.style.marginTop = '8px';
            toast.style.color = '#fff';
            toast.style.fontWeight = '600';
            toast.style.boxShadow = '0 6px 20px rgba(0,0,0,0.25)';
            toast.style.maxWidth = '320px';
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 220ms ease, transform 220ms ease';
            toast.style.transform = 'translateY(-6px)';

            toast.style.background = type === 'success' ? '#22c55e' : '#ef4444';

            container.appendChild(toast);
            // show
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });

            // auto remove
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-6px)';
                setTimeout(() => toast.remove(), 250);
            }, 3000);
        }

        function showConfirmModal(message, onConfirm) {
            // overlay
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.background = 'rgba(0,0,0,0.45)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.zIndex = '10000';

            // modal
            const box = document.createElement('div');
            box.style.background = '#0f2233';
            box.style.color = '#e7f4ff';
            box.style.padding = '18px';
            box.style.borderRadius = '10px';
            box.style.width = 'min(94%,420px)';
            box.style.boxShadow = '0 10px 30px rgba(0,0,0,0.6)';
            box.innerHTML = `
        <div style="font-weight:700; margin-bottom:8px;">Tasdiqlash</div>
        <div style="margin-bottom:14px; color:var(--muted, #9fb7dd);">${message}</div>
        <div style="text-align:right;">
            <button id="confirm-no" style="margin-right:8px; background:#64748b; color:white; border:none; padding:8px 12px; border-radius:8px;">Bekor</button>
            <button id="confirm-yes" style="background:#ef4444; color:white; border:none; padding:8px 12px; border-radius:8px;">O'chirish</button>
        </div>`;

            overlay.appendChild(box);
            document.body.appendChild(overlay);

            // handlers
            box.querySelector('#confirm-no').addEventListener('click', () => overlay.remove());
            box.querySelector('#confirm-yes').addEventListener('click', () => {
                try {
                    onConfirm();
                } catch (e) {
                    console.error(e);
                }
                overlay.remove();
            });

            // close on ESC
            function onKey(e) {
                if (e.key === 'Escape') {
                    overlay.remove();
                    document.removeEventListener('keydown', onKey);
                }
            }
            document.addEventListener('keydown', onKey);
        }


        (function() {
            // debounce helper
            function debounce(fn, wait) {
                let t;
                return function(...args) {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(this, args), wait);
                };
            }

            // Initialize all peer lists (each mg-card will have its own)
            function initPeerLists() {
                document.querySelectorAll('.mg-card').forEach(card => {
                    const search = card.querySelector('.peer-search');
                    const list = card.querySelector('.peer-list');
                    const failedBtn = card.querySelector('.peer-filter-failed');
                    const clearBtn = card.querySelector('.peer-clear');

                    if (!list) return;

                    const rows = Array.from(list.querySelectorAll('.peer-row'));

                    // Filtering logic
                    const applyFilter = (query = '', failedOnly = false) => {
                        const q = query.trim().toLowerCase();
                        let visible = 0;
                        rows.forEach(row => {
                            const peer = row.dataset.peer.toLowerCase();
                            const hasFailed = parseInt(row.dataset.failed || '0') > 0;
                            const matchesQuery = q === '' || peer.includes(q);
                            const matchesFailed = !failedOnly || hasFailed;
                            if (matchesQuery && matchesFailed) {
                                row.style.display = 'flex';
                                visible++;
                            } else {
                                row.style.display = 'none';
                            }
                        });

                        // optional: if too few visible, show a tiny note (accessible)
                        if (visible === 0 && list.dataset.emptyShown !== '1') {
                            // you could add a "no results" row here if desired
                            list.dataset.emptyShown = '1';
                        }
                    };

                    const debouncedFilter = debounce((e) => {
                        applyFilter(e.target.value, failedBtn.classList.contains('active'));
                    }, 160);

                    if (search) {
                        search.addEventListener('input', debouncedFilter);
                    }

                    if (failedBtn) {
                        failedBtn.addEventListener('click', () => {
                            failedBtn.classList.toggle('active');
                            if (failedBtn.classList.contains('active')) {
                                failedBtn.style.opacity = '1';
                                failedBtn.textContent = 'Failed only ✓';
                            } else {
                                failedBtn.style.opacity = '1';
                                failedBtn.textContent = 'Failed only';
                            }
                            applyFilter(search ? search.value : '', failedBtn.classList.contains(
                                'active'));
                        });
                    }

                    if (clearBtn) {
                        clearBtn.addEventListener('click', () => {
                            if (search) search.value = '';
                            failedBtn.classList.remove('active');
                            applyFilter('', false);
                        });
                    }

                    // initial run
                    applyFilter('', false);

                    // Performance note: if rows.length > 1000 consider server-side search or virtualization.
                });
            }

            // run on DOMContentLoaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPeerLists);
            } else {
                initPeerLists();
            }
        })();
    </script>

</body>

</html>
