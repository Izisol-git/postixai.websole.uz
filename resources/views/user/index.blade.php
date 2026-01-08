@extends('layouts.app')

@section('title', __('messages.admin.users') . ' — ' . ($department->name ?? ''))
@section('page-title', $department->name . ' — ' . __('messages.admin.users'))

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container">

    <!-- Topbar -->
    <div class="topbar d-flex justify-content-between align-items-center mb-3">
        <div class="title">
            <span style="font-weight:800; color:var(--accent)">{{ config('app.name', 'POSTIX AI') }}</span>
            <span class="breadcrumbs"> / <a href="{{ route('departments.dashboard', $department->id) }}"
                    style="color:var(--muted); text-decoration:none;">{{ $department->name }}</a>
                → <span style="color:var(--text)">{{__('messages.admin.users') }}</span></span>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('users.create') }}" class="btn btn-sm btn-success">
                + {{ __('messages.admin.add_user') ?? 'Add user' }}
            </a>

            
        </div>
    </div>

    <!-- Department info -->
    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">{{ $department->name }}</h4>
                <div class="text-muted small">{{ $department->description ?? '' }}</div>
            </div>

            <div class="text-end">
                <div class="text-muted small">
                    {{ __('messages.admin.users') }}: <strong>{{ $users->total() }}</strong>
                </div>
                <div class="text-muted small">
                    {{ __('messages.admin.phones') }}: <strong>{{ $department->users()->join('user_phones','users.id','user_phones.user_id')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Users compact list --}}
    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">{{ __('messages.admin.users') }}</h5>

            <div class="d-flex gap-2 align-items-center">
                <input id="usersSearch" class="form-control form-control-sm" type="search" placeholder="{{ __('messages.admin.search_users') ?? 'Search users...' }}" style="width:220px;">
                <button class="btn btn-sm btn-outline-secondary" id="toggleAll">Toggle all</button>
            </div>
        </div>

        <div id="usersList" class="mt-2">
            @foreach ($users as $user)
                @php
                    $userBanned = $user->ban?->active ?? false;
                    $activePhone = $user->phones->firstWhere('is_active', 1);
                @endphp

                <div class="user-line d-flex justify-content-between align-items-center p-2 mb-2" data-user-id="{{ $user->id }}" style="border-radius:8px; background:var(--card);">
                    <div style="display:flex; gap:12px; align-items:center; min-width:0;">
                        <div style="width:40px; height:40px; border-radius:8px; background:linear-gradient(90deg,var(--accent),var(--accent-2)); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700;">
                            {{ strtoupper(substr($user->name ?? $user->username ?? 'U',0,1)) }}
                        </div>
                        <div style="min-width:0;">
                            <div style="font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:320px;">{{ $user->name ?? '—' }}</div>
                            <div class="text-muted small">
                                {{ $user->telegram_id ? '@'.$user->telegram_id : __('messages.admin.no_telegram') }}
                                • {{ $user->role?->name ?? __('messages.admin.no_role') }}
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; gap:8px; align-items:center; min-width:360px; flex-wrap:wrap; justify-content:flex-end;">
                        {{-- Phone select --}}
                        <select class="form-select form-select-sm phone-select" data-user-id="{{ $user->id }}" style="width:180px;">
                            @foreach ($user->phones as $phone)
                                <option value="{{ $phone->id }}" {{ $phone->is_active ? 'selected' : '' }} data-phone-banned="{{ $phone->ban?->active ? '1' : '0' }}">
                                    {{ $phone->phone }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Add phone --}}
                        <a href="{{ route('telegram.login', ['user_id' => $user->id]) }}" class="btn btn-sm btn-success">
                            {{ __('messages.admin.add_phone') ?? 'Add Phone' }}
                        </a>

                        {{-- Show --}}
                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-primary">
                            {{ __('messages.admin.details') ?? 'Details' }}
                        </a>

                        {{-- Ban / Unban --}}
                        <button type="button"
                                id="user-ban-btn-{{ $user->id }}"
                                class="btn btn-sm"
                                style="background: {{ $userBanned ? '#ef4444' : '#6b7280' }}; color:#fff;"
                                onclick="handleUserBanButton({{ $user->id }}, {{ $userBanned ? 'true' : 'false' }})">
                            {{ $userBanned ? __('messages.admin.unban') ?? 'Unban' : __('messages.admin.ban') ?? 'Ban' }}
                        </button>

                        {{-- Delete (requires confirm modal) --}}
                        <button type="button" class="btn btn-sm btn-danger js-confirm-action"
                                data-action="{{ route('users.destroy', $user->id) }}"
                                data-method="DELETE"
                                data-verb="{{ __('messages.admin.delete_user') ?? 'Delete user' }}"
                                data-require-name="{{ $user->name ?? $user->id }}">
                            {{ __('messages.admin.delete') ?? 'Delete' }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">
            {{ $users->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>

</div>

{{-- Toast container --}}
<div id="toast-container" style="position:fixed; top:20px; right:20px; z-index:99999;"></div>

{{-- Confirm modal (reused) --}}
<div id="confirmOverlay" style="display:none; position:fixed; inset:0; align-items:center; justify-content:center; z-index:99998;">
    <div style="width:100%; max-width:520px; background:var(--card); color:var(--text); border-radius:12px; padding:18px; box-shadow:0 20px 60px rgba(0,0,0,.6);">
        <h5 id="confirmTitle">{{ __('messages.admin.confirm') ?? 'Confirm' }}</h5>
        <p id="confirmDesc" class="text-muted"></p>

        <div id="confirmStep1" class="d-flex justify-content-end gap-2">
            <button id="confirmCancel" class="btn btn-sm btn-secondary">{{ __('messages.admin.cancel') ?? 'Cancel' }}</button>
            <button id="confirmContinue" class="btn btn-sm btn-primary">{{ __('messages.admin.continue') ?? 'Continue' }}</button>
        </div>

        <div id="confirmStep2" style="display:none; margin-top:12px;">
            <div class="mb-2">{{ __('messages.admin.confirm_type_name') ?? 'Type the name to confirm' }}</div>
            <input id="confirmInput" class="form-control form-control-sm mb-2" />
            <div class="d-flex justify-content-end gap-2">
                <button id="confirmBack" class="btn btn-sm btn-outline-secondary">{{ __('messages.admin.back') ?? 'Back' }}</button>
                <button id="confirmFinal" class="btn btn-sm btn-danger" disabled>{{ __('messages.admin.delete') ?? 'Delete' }}</button>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

/* ---------- BAN / UNBAN AJAX ---------- */
async function doBanAction(type, id, explicitAction = null, startsAt = null) {
    const payload = { bannable_type: type, bannable_id: id };
    if (explicitAction) payload.action = explicitAction;
    if (startsAt) payload.starts_at = startsAt;

    try {
        const res = await fetch('/admin/ban-unban', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.success) {
            showToast(data.message || '{{ __("messages.admin.error") ?? "Error" }}', 'error');
            return data;
        }

        showToast(data.message || '{{ __("messages.admin.success") ?? "Success" }}', 'success');

        // Update UI for user button if present
        const userBtn = document.getElementById(`user-ban-btn-${id}`);
        if (userBtn && type === 'user') {
            if (data.is_banned) {
                userBtn.textContent = '{{ __("messages.admin.unban") ?? "Unban" }}';
                userBtn.style.background = '#ef4444';
            } else {
                userBtn.textContent = '{{ __("messages.admin.ban") ?? "Ban" }}';
                userBtn.style.background = '#6b7280';
            }
        }

        return data;
    } catch (err) {
        console.error(err);
        showToast('{{ __("messages.admin.server_error") ?? "Server error" }}', 'error');
        return null;
    }
}

function handleUserBanButton(userId, userBanned) {
    const btn = document.getElementById(`user-ban-btn-${userId}`);
    if (!btn) return;
    btn.disabled = true;
    if (userBanned) {
        doBanAction('user', userId, 'unban').finally(() => btn.disabled = false);
    } else {
        // immediate ban (no schedule)
        doBanAction('user', userId).finally(() => btn.disabled = false);
    }
}

/* ---------- Activate phone (select change) ---------- */
// expects route POST /users/{user}/phones/{phone}/activate returning { success: true }
document.querySelectorAll('.phone-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const phoneId = this.value;
        const userId = this.dataset.userId;
        if (!phoneId || !userId) return;
        const url = `/users/${userId}/phones/${phoneId}/activate`;

        const orig = this;
        orig.disabled = true;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        }).then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                showToast(data.message || '{{ __("messages.admin.error_phone_activate") ?? "Failed to activate phone" }}', 'error');
                // TODO: optionally roll back selection (hard to determine previous)
            } else {
                showToast(data.message || '{{ __("messages.admin.phone_activated") ?? "Phone activated" }}', 'success');
                // update phone-banned attr on selected option if provided
                const opt = orig.options[orig.selectedIndex];
                if (opt && data.is_banned !== undefined) opt.setAttribute('data-phone-banned', data.is_banned ? '1' : '0');
            }
        }).catch(() => {
            showToast('{{ __("messages.admin.server_error") ?? "Server error" }}', 'error');
        }).finally(() => orig.disabled = false);
    });
});

/* ---------- Confirm modal logic (reused) ---------- */
(function() {
    const overlay = document.getElementById('confirmOverlay');
    const step1 = document.getElementById('confirmStep1');
    const step2 = document.getElementById('confirmStep2');
    const desc = document.getElementById('confirmDesc');
    const input = document.getElementById('confirmInput');
    const finalBtn = document.getElementById('confirmFinal');

    let activeConfig = null;

    function openConfirm(config) {
        activeConfig = Object.assign({
            action: '#',
            method: 'POST',
            verb: '{{ __("messages.admin.confirm") ?? "Confirm" }}',
            requireName: '',
            text: ''
        }, config || {});

        // show description
        const text = activeConfig.text || activeConfig.requireName || activeConfig.verb;
        desc.textContent = `{{ __('messages.admin.users.confirm') }}`;

        if (activeConfig.requireName) {
            step1.style.display = 'none';
            step2.style.display = 'block';
            input.value = '';
            finalBtn.disabled = true;
        } else {
            step1.style.display = 'block';
            step2.style.display = 'none';
        }

        overlay.style.display = 'flex';
    }

    function closeConfirm() {
        overlay.style.display = 'none';
        activeConfig = null;
    }

    document.getElementById('confirmCancel').addEventListener('click', closeConfirm);
    document.getElementById('confirmContinue').addEventListener('click', function() {
        if (!activeConfig) return;
        if (activeConfig.requireName) {
            step1.style.display = 'none';
            step2.style.display = 'block';
            input.focus();
        } else {
            doSubmit();
        }
    });

    document.getElementById('confirmBack').addEventListener('click', function() {
        step2.style.display = 'none';
        step1.style.display = 'block';
    });

    input.addEventListener('input', function() {
        finalBtn.disabled = (input.value !== (activeConfig.requireName || ''));
    });

    finalBtn.addEventListener('click', function() {
        if (!activeConfig) return;
        if (activeConfig.requireName && input.value !== activeConfig.requireName) {
            showToast('{{ __("messages.admin.confirm_mismatch") ?? "Name mismatch" }}', 'error');
            return;
        }
        doSubmit();
    });

    function doSubmit() {
        if (!activeConfig) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = activeConfig.action;
        form.style.display = 'none';

        const token = document.createElement('input');
        token.type = 'hidden'; token.name = '_token'; token.value = csrfToken;
        form.appendChild(token);

        const method = (activeConfig.method || 'POST').toUpperCase();
        if (method !== 'POST') {
            const m = document.createElement('input'); m.type='hidden'; m.name='_method'; m.value=method; form.appendChild(m);
        }

        document.body.appendChild(form);
        form.submit();
    }

    // Attach to .js-confirm-action elements
    document.addEventListener('click', function(e) {
        const el = e.target.closest('.js-confirm-action');
        if (!el) return;
        e.preventDefault();

        const cfg = {
            action: el.getAttribute('data-action') || el.getAttribute('href') || '#',
            method: el.getAttribute('data-method') || 'POST',
            verb: el.getAttribute('data-verb') || '',
            requireName: el.getAttribute('data-require-name') || '',
            text: el.getAttribute('data-text') || ''
        };
        openConfirm(cfg);
    });

    window.openConfirm = openConfirm;
})();

/* ---------- Toast helper ---------- */
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const t = document.createElement('div');
    t.innerHTML = message;
    Object.assign(t.style, {
        background: type === 'success' ? '#16a34a' : '#ef4444',
        color: '#fff',
        padding: '8px 12px',
        borderRadius: '8px',
        marginTop: '8px',
        boxShadow: '0 6px 18px rgba(0,0,0,0.2)',
        fontWeight: 700,
        maxWidth: '320px',
        opacity: '0',
        transform: 'translateY(-6px)',
        transition: 'opacity .2s, transform .2s'
    });
    container.appendChild(t);
    requestAnimationFrame(() => { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; });
    setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateY(-6px)'; setTimeout(()=>t.remove(),250); }, 3000);
}

/* ---------- Quick UI helpers ---------- */
document.getElementById('toggleAll')?.addEventListener('click', function() {
    const list = document.getElementById('usersList');
    if (!list) return;
    const lines = list.querySelectorAll('.user-line');
    lines.forEach(l => {
        l.style.background = l.style.background === 'transparent' ? 'var(--card)' : 'transparent';
    });
});

/* ---------- Simple client-side search (works on current page only) ---------- */
document.getElementById('usersSearch')?.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('#usersList .user-line');
    rows.forEach(r => {
        const txt = (r.querySelector('.user-name')?.textContent || r.textContent || '').toLowerCase();
        r.style.display = txt.includes(q) ? 'flex' : 'none';
    });
});
</script>
@endsection
