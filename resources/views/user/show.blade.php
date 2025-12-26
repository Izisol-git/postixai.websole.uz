<!DOCTYPE html>
<html lang="uz">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>{{ $user->name ?? 'User' }} — Batafsil — Postix Ai</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
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

        .phones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .phone-pill {
            background: #ffffff;
            padding: 8px 10px;
            border-radius: 10px;
            display: flex;
            gap: 8px;
            align-items: center;
            color: #0f172a;
            /* matn qoraroq */
        }

        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid rgba(255, 255, 255, 0.04);
            padding: 6px 10px;
            border-radius: 8px;
        }

        .btn-primary-custom {
            background: var(--accent);
            color: white;
            border-radius: 8px;
            padding: 6px 10px;
            border: none;
        }

        .btn-danger-custom {
            background: var(--danger);
            color: white;
            border-radius: 8px;
            padding: 6px 10px;
            border: none;
        }

        .mg-card {
            background: rgba(255, 255, 255, 0.02);
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.03);
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

        .peer-list {
            max-height: 220px;
            overflow: auto;
            padding-right: 6px;
            margin-top: 8px;
        }

        .peer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 8px;
            border-radius: 6px;
            margin-bottom: 6px;
            background: rgba(255, 255, 255, 0.02);
        }

        .small-note {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .search-row {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }

        .form-control-dark {
            background: #071827;
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 6px;
            padding: 6px 10px;
        }

        .pagination {
            display: flex;
            list-style: none;
            gap: 8px;
            padding: 0;
            justify-content: center;
            margin-top: 12px;
        }

        /* toast */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast-item {
            padding: 10px 14px;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            max-width: 360px;
            opacity: 0;
            transform: translateY(-6px);
            transition: all .22s ease;
        }

        .toast-show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- Top -->
        <div class="topbar">
            <div class="title">
                <span style="font-weight:800; color:var(--yellow)">{{ $user->name }}</span>
                <span class="breadcrumbs"> / <a href="{{ route('departments.index') }}"
                        style="color:var(--muted); text-decoration:none;">Departments</a> → <a
                        href="{{ route('departments.show', $user->department_id) }}"
                        style="color:var(--muted); text-decoration:none;">Department</a> → <span
                        style="color:var(--text)">{{ $user->name }}</span></span>
            </div>

            <div style="display:flex; gap:8px; align-items:center;">
                <a href="{{ route('departments.show', $user->department_id) }}" class="btn-ghost">← Back</a>
                <a href="{{ route('users.edit', $user->id) }}" class="btn-primary-custom">Edit</a>
                <form id="deleteUserForm" action="{{ route('users.destroy', $user->id) }}" method="POST"
                    style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn-danger-custom"
                        onclick="confirmDelete('Bu foydalanuvchini o‘chirib tashlamoqchimisiz?', deleteUserConfirmed)">Delete</button>
                </form>
            </div>
        </div>

        <!-- Header card -->
        <div class="card">
            <h3>{{ $user->name }} — Batafsil</h3>
            <div class="stats">
                <div class="stat">
                    <h4>{{ $phonesCount ?? 0 }}</h4>
                    <p>Telefonlar</p>
                </div>
                <div class="stat">
                    <h4>{{ $activePhonesCount ?? 0 }}</h4>
                    <p>Aktiv telefonlar</p>
                </div>
                <div class="stat">
                    <h4>{{ $totals->groups_count ?? 0 }}</h4>
                    <p>Operatsiyalar</p>
                </div>
                <div class="stat">
                    <h4>{{ $totals->messages_count ?? 0 }}</h4>
                    <p>Xabarlar soni</p>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700;color:var(--text)">{{ $user->email ?? '—' }}</div>
                    <div class="small-note">Telegram: {{ $user->telegram_id ?? '—' }}</div>
                </div>

                <div class="phones">
                    @foreach ($user->phones as $phone)
                        <div class="phone-pill">
                            <div style="font-weight:700;">{{ $phone->phone }}</div>
                            @if ($phone->is_active)
                                <div class="small-note" style="color:var(--accent);">active</div>
                            @endif
                        </div>
                    @endforeach

                    <form action="{{ route('telegram.login') }}" method="GET" style="display:inline;">
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <button type="submit" class="btn-primary-custom" style="margin-left:6px;">Add Phone</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Message Groups -->
        <div style="margin-top:18px;">
            <h5 style="color:var(--yellow); margin-bottom:6px;">Operatsiyalar</h5>

            <form method="GET" action="{{ route('users.show', $user->id) }}" class="search-form mb-3"
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
                                {{ optional($group->phone)->phone ?? '—' }} •
                                {{ optional($group->phone->user)->name ?? '—' }}
                            </div>
                        </div>

                        <div style="display:flex; gap:8px;">
                            <a href="#" class="btn-primary-custom"
                                onclick="return onRefresh(event, {{ $gid }})">Refresh</a>
                            <a href="#" class="btn-danger-custom"
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
                        <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                            <input type="search" class="form-control-dark" placeholder="Search peers..."
                                oninput="filterPeers(this, {{ $gid }})" data-gid="{{ $gid }}"
                                style="flex:1;">
                            <button type="button" class="btn-primary-custom" title="Show only failed"
                                onclick="filterFailed({{ $gid }}, this)">Failed only</button>
                        </div>

                        <div class="peer-list" id="peer-list-{{ $gid }}">
                            @foreach ($peers as $peer => $statuses)
                                @php $peerTotal = array_sum($statuses); @endphp
                                <div class="peer-row" data-peer="{{ $peer }}"
                                    data-failed="{{ $statuses['failed'] ?? 0 }}">
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
                                                    style="display:flex;align-items:center;gap:4px;padding:3px 7px;border-radius:6px;font-size:11px;font-weight:700;white-space:nowrap;background:{{ $info['color'] }}22;color:{{ $info['color'] }};">
                                                    @if ($key === 'sent')
                                                        ✓
                                                    @elseif ($key === 'failed')
                                                        ✕
                                                    @elseif ($key === 'canceled')
                                                        ⦸
                                                    @elseif ($key === 'scheduled')
                                                        ⏳
                                                    @endif
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

        <!-- Messages search & list -->
        {{-- <div class="card" style="margin-top:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h5 style="color:var(--yellow); margin:0;">Xabarlar</h5>

                <form id="msgSearchForm" method="GET" action="{{ route('users.show', $user->id) }}">
                    <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="Search messages or peers..." class="form-control-dark" style="min-width:260px; display:inline-block;">
                    <button type="submit" class="btn-primary-custom" style="margin-left:8px;">Search</button>
                </form>
            </div>

            <hr style="border-color: rgba(255,255,255,0.04); margin:8px 0;">

            <div>
                @foreach ($messages as $m)
                    <div style="background:rgba(255,255,255,0.01); padding:10px; border-radius:8px; margin-bottom:8px;">
                        <div style="display:flex; justify-content:space-between; gap:8px;">
                            <div>
                                <strong style="color:var(--text)">{{ $m->peer }}</strong>
                                <div class="small-note">Status: {{ $m->status }} • Sent at: {{ $m->send_at ?? $m->created_at ?? '-' }}</div>
                            </div>
                            <div class="small-note">{{ Str::limit($m->message_text, 160) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-2">
                {{ $messages->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div> --}}

    </div>

    {{-- Toast container --}}
    <div id="toast-container"></div>

    <script>
        // Basic helpers
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const t = document.createElement('div');
            t.className = 'toast-item';
            t.innerHTML = message;
            t.style.background = (type === 'success') ? '#22c55e' : (type === 'error') ? '#ef4444' : '#f59e0b';
            container.appendChild(t);
            requestAnimationFrame(() => t.classList.add('toast-show'));
            setTimeout(() => {
                t.classList.remove('toast-show');
                setTimeout(() => t.remove(), 250);
            }, 3000);
        }

        // show session flashes
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success'))
                showToast(@json(session('success')), 'success');
            @endif
            @if (session('error'))
                showToast(@json(session('error')), 'error');
            @endif
        });

        // peer filtering
        function filterPeers(input, gid) {
            const q = input.value.trim().toLowerCase();
            const list = document.getElementById('peer-list-' + gid);
            if (!list) return;
            list.querySelectorAll('.peer-row').forEach(row => {
                const peer = (row.dataset.peer || '').toLowerCase();
                row.style.display = (!q || peer.includes(q)) ? 'flex' : 'none';
            });
        }

        function filterFailed(gid, btn) {
            const list = document.getElementById('peer-list-' + gid);
            if (!list) return;
            const active = btn.dataset.active === '1';
            btn.dataset.active = active ? '0' : '1';
            btn.style.opacity = active ? '1' : '0.95';
            list.querySelectorAll('.peer-row').forEach(row => {
                const hasFailed = parseInt(row.dataset.failed || '0') > 0;
                row.style.display = (!btn.dataset.active || btn.dataset.active === '0') ? 'flex' : (hasFailed ?
                    'flex' : 'none');
            });
        }

        // delete flow
        function confirmDelete(message, onConfirm) {
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.background = 'rgba(0,0,0,0.45)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.zIndex = '10000';

            const box = document.createElement('div');
            box.style.background = '#0f2233';
            box.style.color = '#e7f4ff';
            box.style.padding = '18px';
            box.style.borderRadius = '10px';
            box.style.width = 'min(94%,420px)';
            box.style.boxShadow = '0 10px 30px rgba(0,0,0,0.6)';
            box.innerHTML =
                `<div style="font-weight:700; margin-bottom:8px;">Tasdiqlash</div><div style="margin-bottom:14px; color:var(--muted,#9fb7dd);">${message}</div><div style="text-align:right;"><button id="cd-no" style="margin-right:8px; background:#64748b; color:white; border:none; padding:8px 12px; border-radius:8px;">Bekor</button><button id="cd-yes" style="background:#ef4444; color:white; border:none; padding:8px 12px; border-radius:8px;">O'chirish</button></div>`;
            overlay.appendChild(box);
            document.body.appendChild(overlay);

            box.querySelector('#cd-no').addEventListener('click', () => overlay.remove());
            box.querySelector('#cd-yes').addEventListener('click', () => {
                onConfirm();
                overlay.remove();
            });

            document.addEventListener('keydown', function esc(e) {
                if (e.key === 'Escape') {
                    overlay.remove();
                    document.removeEventListener('keydown', esc);
                }
            });
        }

        function deleteUserConfirmed() {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch("{{ route('users.destroy', $user->id) }}", {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
            }).then(async res => {
                if (!res.ok) {
                    const json = await res.json().catch(() => null);
                    throw new Error((json && json.message) ? json.message : 'Server xatosi');
                }
                return res.json().catch(() => ({
                    success: true,
                    message: 'O‘chirildi'
                }));
            }).then(data => {
                showToast(data.message || 'Foydalanuvchi o‘chirildi', 'error');
                setTimeout(() => {
                    // redirect to department show after deletion
                    window.location.href = "{{ route('departments.show', $user->department_id) }}";
                }, 900);
            }).catch(err => {
                console.error(err);
                showToast(err.message || 'Xato yuz berdi', 'error');
            });
        }

        // message group helpers (placeholders)
        function onRefresh(e, id) {
            e.preventDefault();
            showToast('Refresh: ' + id, 'success');
            return false;
        }

        function onCancel(e, id) {
            e.preventDefault();
            if (!confirm('Bu operatsiyani bekor qilmoqchimisiz?')) return false;
            showToast('Cancel: ' + id, 'error');
            return false;
        }
    </script>
</body>

</html>
