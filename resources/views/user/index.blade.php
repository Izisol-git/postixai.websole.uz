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
            {{-- Server-side search form. Enter bosilganda serverga yuboradi --}}
            <form method="GET" action="{{ route('departments.users', $department->id) }}" class="d-flex gap-2 align-items-center">
                <input id="usersSearch" name="q" class="form-control form-control-sm" type="search" value="{{ $q ?? '' }}" placeholder="{{ __('messages.admin.search_users') ?? 'Search users...' }}" style="width:240px;">
                <button class="btn btn-sm btn-outline-secondary" type="submit">{{ __('messages.users.search') ?? 'Search' }}</button>
            </form>

            <a href="{{ route('admin.telegram.new-users') }}" class="btn btn-sm btn-success">
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
                <button class="btn btn-sm btn-outline-secondary" id="toggleAll">Toggle all</button>
            </div>
        </div>

        <div id="usersList" class="mt-2">
            @foreach ($users as $user)
                @php
                    $userBanned = $user->ban?->active ?? false;
                @endphp

                <div class="user-line d-flex justify-content-between align-items-center p-2 mb-2" data-user-id="{{ $user->id }}" style="border-radius:8px; background:var(--card);">
                    <div style="display:flex; gap:12px; align-items:center; min-width:0;">
                        {{-- Avatar or initials --}}
                        <div style="width:40px; height:40px; border-radius:8px; overflow:hidden; flex-shrink:0;">
                            @if($user->avatar_url)
                                <img src="{{ $user->avatar_url }}" alt="avatar" style="width:40px;height:40px;object-fit:cover;display:block;">
                            @else
                                <div style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6366f1,#22d3ee);color:#fff;font-weight:800;">
                                    {{ $user->avatar_letter }}
                                </div>
                            @endif
                        </div>

                        <div style="min-width:0;">
                            <div class="user-name" style="font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:320px;">
                                {{ $user->name ?? '—' }}
                            </div>
                            <div class="text-muted small">
                                {{ $user->email ? $user->email : '' }} •
                                {{ $user->telegram_id ? $user->telegram_id : __('messages.admin.no_telegram') }}
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
                        

                        {{-- Show --}}
                        <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-primary">
                            {{ __('messages.admin.details') ?? 'Details' }}
                        </a>

                        {{-- Ban / Unban --}}
                        {{-- NOTE: removed inline onclick to avoid double requests. JS will attach listeners.
                             We keep initial state using data-* attributes. --}}
                        <button type="button"
                                id="user-ban-btn-{{ $user->id }}"
                                class="btn btn-sm user-ban-btn"
                                data-user-id="{{ $user->id }}"
                                data-banned="{{ $userBanned ? '1' : '0' }}"
                                style="background: {{ $userBanned ? '#ef4444' : '#6b7280' }}; color:#fff;">
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
/*
  Finalized JS for users list:
  - Uses .user-ban-btn buttons (no inline onclick) to avoid double-requests
  - Works with controller that returns -> success() helper format:
      { status: 'success', message: '...', data: { is_banned: true|false, ... } }
  - Robust error handling and single-request locking
*/

document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    /* ---------- Toast helper (existing UI-friendly) ---------- */
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
            transition: 'opacity .18s, transform .18s'
        });
        container.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; });
        setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateY(-6px)'; setTimeout(()=>t.remove(),250); }, 3000);
    }

    /* ---------- Spinner helper ---------- */
    function setBtnLoading(btn, loading) {
        if (!btn) return;
        if (loading) {
            btn.dataset._orig = btn.innerHTML;
            btn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin .8s linear infinite"></span>';
            btn.disabled = true;
            if (!document.getElementById('spin-style')) {
                const s = document.createElement('style');
                s.id = 'spin-style';
                s.innerHTML = '@keyframes spin{to{transform:rotate(360deg)}}';
                document.head.appendChild(s);
            }
        } else {
            btn.innerHTML = btn.dataset._orig || btn.innerHTML;
            btn.disabled = false;
            delete btn.dataset._orig;
        }
    }

    /* ---------- Update UI after response ---------- */
    function applyButtonState(btn, isBanned) {
        if (!btn) return;
        btn.dataset.banned = isBanned ? '1' : '0';
        btn.style.background = isBanned ? '#ef4444' : '#6b7280';
        btn.style.color = '#fff';
        btn.textContent = isBanned ? ('{{ __("messages.admin.unban") ?? "Unban" }}') : ('{{ __("messages.admin.ban") ?? "Ban" }}');
    }

    function applyCounts(userId, raw) {
        if (!raw) return;
        const ops = raw.ops_count ?? (raw.data && raw.data.ops_count);
        const msgs = raw.msgs_count ?? (raw.data && raw.data.msgs_count);
        if (typeof ops !== 'undefined') {
            const el = document.getElementById('ops-count-' + userId);
            if (el) el.textContent = ops;
        }
        if (typeof msgs !== 'undefined') {
            const el2 = document.getElementById('msgs-count-' + userId);
            if (el2) el2.textContent = msgs;
        }
    }

    /* ---------- Single request executor ---------- */
    async function doBanRequest(payload) {
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

            let json = {};
            try { json = await res.json(); } catch (e) { json = {}; }

            // Normalize success check: controller returns { status: 'success', ... }
            const ok = res.ok && (String(json.status).toLowerCase() === 'success' || json.success === true);

            if (!ok) {
                // if validation errors
                if (json.errors) {
                    const errs = [];
                    Object.values(json.errors).forEach(arr => { if (Array.isArray(arr)) errs.push(...arr); });
                    if (errs.length) {
                        showToast(errs.join(', '), 'error');
                        return { ok: false, raw: json };
                    }
                }
                showToast(json.message || '{{ __("messages.admin.error") ?? "Error" }}', 'error');
                return { ok: false, raw: json };
            }

            // success
            showToast(json.message || '{{ __("messages.admin.success") ?? "Success" }}', 'success');

            return { ok: true, raw: json };
        } catch (err) {
            console.error('Ban request failed', err);
            showToast('{{ __("messages.admin.server_error") ?? "Server error" }}', 'error');
            return { ok: false, error: err };
        }
    }

    /* ---------- Attach click handlers to ban buttons ---------- */
    function attachBanButtons() {
        document.querySelectorAll('.user-ban-btn').forEach(btn => {
            if (btn.dataset.bound === '1') return; // already bound
            btn.dataset.bound = '1';

            btn.addEventListener('click', async function (e) {
                if (btn.dataset.loading === '1') return;
                const userId = btn.dataset.userId || btn.getAttribute('data-user-id') || btn.getAttribute('data-userid');
                if (!userId) return;

                const isBanned = btn.dataset.banned === '1';
                btn.dataset.loading = '1';
                setBtnLoading(btn, true);

                const payload = {
                    bannable_type: 'user',
                    bannable_id: parseInt(userId, 10),
                };
                if (isBanned) payload.action = 'unban';

                const result = await doBanRequest(payload);

                if (result && result.ok) {
                    // controller returns json.data.is_banned
                    const raw = result.raw || {};
                    const isBannedResp = (raw.data && typeof raw.data.is_banned !== 'undefined') ? raw.data.is_banned : null;

                    // if server provided definite flag, use it; otherwise toggle locally
                    const nowBanned = (isBannedResp !== null && typeof isBannedResp !== 'undefined') ? !!isBannedResp : !isBanned;

                    applyButtonState(btn, nowBanned);
                    applyCounts(userId, raw);
                }

                setBtnLoading(btn, false);
                delete btn.dataset.loading;
            });
        });
    }

    attachBanButtons();

    /* ---------- Activate phone (select change) ---------- */
    // document.querySelectorAll('.phone-select').forEach(sel => {
    //     sel.addEventListener('change', async function() {
    //         const phoneId = this.value;
    //         const userId = this.dataset.userId;
    //         if (!phoneId || !userId) return;
    //         const url = `/users/${userId}/phones/${phoneId}/activate`;

    //         const orig = this;
    //         orig.disabled = true;

    //         try {
    //             const res = await fetch(url, {
    //                 method: 'POST',
    //                 headers: {
    //                     'X-CSRF-TOKEN': csrfToken,
    //                     'Accept': 'application/json'
    //                 }
    //             });
    //             const data = await res.json().catch(() => ({}));
    //             if (!res.ok || !(String(data.status).toLowerCase() === 'success' || data.success === true)) {
    //                 showToast(data.message || '{{ __("messages.admin.error_phone_activate") ?? "Failed to activate phone" }}', 'error');
    //             } else {
    //                 showToast(data.message || '{{ __("messages.admin.phone_activated") ?? "Phone activated" }}', 'success');
    //                 const opt = orig.options[orig.selectedIndex];
    //                 if (opt && data.data && typeof data.data.is_banned !== 'undefined') opt.setAttribute('data-phone-banned', data.data.is_banned ? '1' : '0');
    //             }
    //         } catch (err) {
    //             console.error(err);
    //             showToast('{{ __("messages.admin.server_error") ?? "Server error" }}', 'error');
    //         } finally {
    //             orig.disabled = false;
    //         }
    //     });
    // });

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

    /* ---------- Toggle all helper ---------- */
    document.getElementById('toggleAll')?.addEventListener('click', function() {
        const list = document.getElementById('usersList');
        if (!list) return;
        const lines = list.querySelectorAll('.user-line');
        lines.forEach(l => {
            l.style.background = l.style.background === 'transparent' ? 'var(--card)' : 'transparent';
        });
    });

    /* ---------- Instant client-side search ---------- */
    document.getElementById('usersSearch')?.addEventListener('input', function(e) {
        // if user pressed Enter we want normal form submit; else instant filter
        if (e.inputType === 'insertLineBreak') return;

        const q = this.value.trim().toLowerCase();
        const rows = document.querySelectorAll('#usersList .user-line');
        rows.forEach(r => {
            const txt = (r.querySelector('.user-name')?.textContent || r.textContent || '').toLowerCase();
            r.style.display = txt.includes(q) ? 'flex' : 'none';
        });
    });

}); // DOMContentLoaded
</script>
@endsection
