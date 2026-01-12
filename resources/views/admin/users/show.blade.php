@extends('layouts.app')

@section('title', $user->name . ' — ' . __('messages.users.title'))
@section('page-title', $user->name)

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* ===== Theme variables ===== */
        :root {
            --bg: #071427;
            --card: #0f2233;
            --card-grad-1: rgba(255, 255, 255, 0.02);
            --card-grad-2: rgba(255, 255, 255, 0.01);
            --text: #e6eef6;
            --muted: #9fb7dd;
            --accent: #7c3aed;
            --border-soft: rgba(255, 255, 255, 0.04);
            --shadow: 0 10px 30px rgba(2, 6, 23, 0.6);
        }

        /* Light mode overrides */
        body.light {
            --bg: #f6f8fb;
            --card: #ffffff;
            --card-grad-1: rgba(0, 0, 0, 0.02);
            --card-grad-2: rgba(0, 0, 0, 0.01);
            --text: #0b1220;
            --muted: #6b7280;
            --accent: #2563eb;
            --border-soft: rgba(0, 0, 0, 0.06);
            --shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        }

        /* ===== Base ===== */
        body {
            background: var(--bg);
            color: var(--text);
            transition: background .22s ease, color .22s ease;
        }

        /* ===== Panels / Cards ===== */
        .panel {
            background: linear-gradient(180deg,
                    var(--card-grad-1),
                    var(--card-grad-2)), var(--card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-soft);
        }

        /* ===== Avatar ===== */
        .avatar-large {
            width: 160px;
            height: 160px;
            border-radius: 16px;
            object-fit: cover;
        }

        .initials {
            width: 160px;
            height: 160px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 48px;
            color: #fff;
            background: var(--accent);
        }

        /* ===== Text helpers ===== */
        .muted {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .small-muted {
            color: var(--muted);
            font-size: 0.85rem;
        }

        .accent {
            color: var(--accent);
        }

        /* ===== Buttons ===== */
        .btn-ghost {
            background: transparent;
            border: 1px solid var(--border-soft);
            color: var(--text);
        }

        .btn-ghost:hover {
            background: var(--card-grad-1);
        }

        /* ===== Lists ===== */
        .list-group-item {
            background: transparent;
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            margin-bottom: 8px;
            color: var(--text);
        }

        /* ===== Toast ===== */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
        }

        /* ===== Form controls FIX (dark / light) ===== */
        .form-control,
        .form-select,
        input,
        textarea {
            background-color: var(--card) !important;
            color: var(--text) !important;
            border: 1px solid var(--border-soft) !important;
        }

        .form-control::placeholder,
        textarea::placeholder {
            color: var(--muted) !important;
            opacity: 1;
        }

        /* focus state */
        .form-control:focus,
        .form-select:focus,
        input:focus,
        textarea:focus {
            background-color: var(--card) !important;
            color: var(--text) !important;
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25) !important;
        }

        /* disabled / readonly */
        .form-control:disabled,
        .form-control[readonly] {
            background-color: var(--card-grad-2) !important;
            color: var(--muted) !important;
        }

        /* Simple modal styles */
        .modal-backdrop-custom {
            position: fixed;
            inset: 0;
            background: rgba(2,6,23,0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99998;
        }

        .modal-custom {
            background: var(--card);
            border-radius: 10px;
            padding: 18px;
            width: 420px;
            max-width: calc(100% - 32px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.6);
            border: 1px solid rgba(255,255,255,0.03);
            color: var(--text);
        }

        .modal-custom h4 {
            margin: 0 0 8px 0;
            color: var(--yellow, #facc15);
        }

        .modal-actions {
            display:flex;
            justify-content:flex-end;
            gap:8px;
            margin-top:12px;
        }
    </style>

<div id="toast-container" style="position:fixed; top:60px; right:20px; z-index:99999;"></div>

    <div class="container" style="max-width:1000px;">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex gap-4 mt-3" style="align-items:flex-start;">

            <!-- LEFT: Profile card -->
            <div style="width:300px;">
                <div class="panel text-center">
                    @if ($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar->path) }}" alt="avatar"
                            class="avatar-large mx-auto d-block">
                    @else
                        <div class="initials mx-auto" style="background: linear-gradient(135deg,#6366f1,#22d3ee);">
                            {{ strtoupper(mb_substr($user->name ?? ($user->username ?? 'U'), 0, 1)) }}
                        </div>
                    @endif

                    <h5 class="mt-3 mb-0">{{ $user->name }}</h5>
                    <div class="muted small">
                        {{ $user->email ?? __('messages.users.no_email') }}<br>
                        {{ $user->telegram_id ? ' ' . $user->telegram_id : __('messages.users.no_telegram') }}
                    </div>

                    <div class="mt-3 d-grid gap-2">
                        <a href="{{ route('departments.users', $user->department_id) }}"
                            class="btn btn-ghost btn-sm">{{ __('messages.users.back_to_list') ?? 'Back' }}</a>

                        @can('delete', $user)
                            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                                onsubmit="return confirm('{{ __('messages.users.delete_confirm') ?? 'Are you sure?' }}');">
                                @csrf @method('POST')
                                <button type="submit"
                                    class="btn btn-sm btn-danger">{{ __('messages.users.delete_user') ?? 'Delete user' }}</button>
                            </form>
                        @endcan
                    </div>
                </div>

                <!-- Quick stats / meta + actions -->
                <div class="panel mt-3 small-muted">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div><strong
                                    id="ops-count-{{ $user->id }}">{{ $operationsCount ?? $user->operations()->count() }}</strong>
                                {{ __('messages.users.operations_count') ?? 'operations' }}</div>
                            <div class="mt-1"><strong
                                    id="msgs-count-{{ $user->id }}">{{ $messagesCount ?? $user->messages()->count() }}</strong>
                                {{ __('messages.users.messages_count') ?? 'messages' }}</div>
                        </div>

                    </div>

                    <div class="mt-2 d-flex gap-2">
                        {{-- Ban / Unban button --}}
                        @php $isBanned = $user->ban?->active ?? false; @endphp
                        <button type="button" id="user-ban-btn-{{ $user->id }}" class="btn btn-sm w-100 user-ban-btn"
                            style="background: {{ $isBanned ? '#ef4444' : '#6b7280' }}; color:#fff;"
                            data-user-id="{{ $user->id }}" data-banned="{{ $isBanned ? '1' : '0' }}">
                            {{ $isBanned ? __('messages.admin.unban') ?? 'Unban' : __('messages.admin.ban') ?? 'Ban' }}
                        </button>
                    </div>

                    <div class="mt-2 text-muted small">
                        {{ __('messages.users.registered_at') ?? 'Registered' }}: <span
                            class="muted">{{ $user->created_at->format('Y-m-d') }}</span>
                    </div>
                </div>
            </div>


            <!-- RIGHT: Main content -->
            <div style="flex:1;">
                <!-- Edit card -->
                <form method="POST" action="{{ route('admin.users.update', $user->id) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="panel mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">{{ __('messages.users.edit_user') ?? 'Edit user' }}</h5>
                            <div class="small-muted">{{ __('messages.users.help_edit') ?? '' }}</div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.users.name') ?? 'Name' }}</label>
                                <input name="name" class="form-control bg-transparent text-light"
                                    value="{{ old('name', $user->name) }}" required>
                            </div>
                            @if($user->role->name == 'admin')
                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.users.email') ?? 'Email' }}</label>
                                <input name="email" class="form-control bg-transparent text-light"
                                    value="{{ old('email', $user->email) }}">
                            </div>
                            @endif

                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.users.telegram_id') ?? 'Telegram ID' }}</label>
                                <input name="telegram_id" class="form-control bg-transparent text-light"
                                    value="{{ old('telegram_id', $user->telegram_id) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.users.avatar') ?? 'Avatar' }}</label>
                                <input type="file" name="avatar" accept="image/*" class="form-control" id="avatarInput">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_avatar" value="1"
                                        id="removeAvatar">
                                    <label class="form-check-label small"
                                        for="removeAvatar">{{ __('messages.users.remove_avatar') ?? 'Remove current avatar' }}</label>
                                </div>
                            </div>


                            @if($user->role->name == 'admin')

                            <div class="col-md-6">
                                <label class="form-label">{{ __('messages.users.new_password') ?? 'New password' }}</label>
                                <input type="password" name="password" class="form-control bg-transparent text-light"
                                    placeholder="{{ __('messages.users.leave_empty') ?? 'Leave empty to keep' }}" autocomplete="new-password">
                            </div>
                            @endif
                        </div>

                        <div class="mt-3 text-end">
                            <button
                                class="btn btn-primary">{{ __('messages.users.save_changes') ?? 'Save changes' }}</button>
                        </div>
                    </div>
                </form>

                <!-- Phones list + add/delete with 2-step phone flow -->
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">{{ __('messages.users.phones') ?? 'Phones' }}</h5>
                        <div class="small-muted">{{ __('messages.users.manage_phones_hint') ?? '' }}</div>
                    </div>

                    <div id="phonesList" class="mb-3">
                        @foreach ($user->phones as $phone)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $phone->phone }}</strong>
                                    <div class="text-muted small">
                                        {{ $phone->is_active ? __('messages.users.active') : __('messages.users.inactive') }}
                                    </div>
                                </div>
                                @if($phone->is_active)
                                <div class="d-flex gap-2 align-items-center">
                                    {{-- Replace inline confirm with modal trigger --}}
                                    <button type="button"
                                        class="btn btn-sm btn-outline-danger delete-phone-btn"
                                        data-route="{{ route('telegram.logout', ['user_id' => $user->id, 'phone' => $phone->phone]) }}"
                                        data-phone="{{ $phone->phone }}">
                                        {{ __('messages.users.delete_phone') ?? 'Delete' }}
                                    </button>
                                </div>
                                @endif  
                            </div>
                        @endforeach
                    </div>

                    <!-- 2-step phone flow UI -->
                    <div id="phoneFlow" class="mt-2">
                        <div id="stepPhone">
                            <label
                                class="form-label small-muted">{{ __('messages.users.add_phone_label') ?? 'Add phone' }}</label>
                            <div class="d-flex gap-2">
                                <input id="phoneInput" name="phone" class="form-control form-control-sm"
                                    placeholder="{{ __('messages.users.add_phone_placeholder') ?? 'Enter number' }}">
                                <button id="btnSendPhone"
                                    class="btn btn-sm btn-success">{{ __('messages.users.send_sms') ?? 'Send' }}</button>
                            </div>
                            <div id="phoneError" class="text-danger small d-none mt-1"></div>
                        </div>

                        <div id="stepCode" class="d-none mt-3">
                            <label
                                class="form-label small-muted">{{ __('messages.users.enter_code_label') ?? 'Enter code' }}</label>
                            <div class="d-flex gap-2">
                                <input id="codeInput" class="form-control form-control-sm"
                                    placeholder="{{ __('messages.users.code_placeholder') ?? 'SMS code' }}">
                                <button id="btnVerifyCode"
                                    class="btn btn-sm btn-primary">{{ __('messages.users.verify_code') ?? 'Verify' }}</button>
                            </div>
                            <div id="codeError" class="text-danger small d-none mt-1"></div>
                            <div class="mt-2">
                                <button id="btnBackToPhone"
                                    class="btn btn-sm btn-ghost">{{ __('messages.users.change_phone') ?? 'Change phone' }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm delete modal (single, reused) -->
    <div id="modalBackdrop" class="modal-backdrop-custom" aria-hidden="true">
        <div class="modal-custom" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <h4 id="modalTitle">{{ __('messages.users.phone_delete_confirm') ?? 'Confirm delete' }}</h4>
            <div id="modalBody" class="small-muted">
                {{ __('messages.users.phone_delete_confirm') ?? 'Are you sure you want to delete this phone?' }}
            </div>

            <div class="modal-actions">
                <button type="button" id="modalCancel" class="btn btn-secondary">{{ __('messages.admin.cancel') ?? 'Cancel' }}</button>

                <form id="modalDeleteForm" method="POST" action="">
                    @csrf
                    {{-- If your route needs a method override, add here, e.g. @method('DELETE') --}}
                    <button type="submit" class="btn btn-danger">{{ __('messages.users.delete_phone') ?? 'Delete' }}</button>
                </form>
            </div>
        </div>
    </div>

    <div id="toast-container" class="toast"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            /* -------- unified showToast -------- */
            function showToast(msg, type = 'success') {
                let container = document.getElementById('toast-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'toast-container';
                    Object.assign(container.style, { position:'fixed', top:'20px', right:'20px', zIndex: 99999 });
                    document.body.appendChild(container);
                }
                const t = document.createElement('div');
                t.textContent = msg;
                Object.assign(t.style, {
                    padding: '8px 12px',
                    borderRadius: '8px',
                    color: '#fff',
                    marginTop: '8px',
                    background: type === 'success' ? '#16a34a' : '#ef4444',
                    fontWeight: 700
                });
                container.appendChild(t);
                setTimeout(() => {
                    t.style.opacity = '0';
                    setTimeout(() => t.remove(), 300);
                }, 3000);
            }

            /* -------- doBanAction (normalized) -------- */
            async function doBanAction(type, id, explicitAction = null) {
                const payload = {
                    bannable_type: type,
                    bannable_id: id
                };
                if (explicitAction) payload.action = explicitAction;

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

                    const ok = res.ok && (String(json.status).toLowerCase() === 'success' || json.success === true || json.data);
                    return { ok, json };
                } catch (err) {
                    console.error(err);
                    return { ok: false, json: null };
                }
            }

            /* -------- Attach single handler to all user-ban buttons -------- */
            document.querySelectorAll('.user-ban-btn').forEach(btn => {
                if (btn.dataset.bound === '1') return;
                btn.dataset.bound = '1';

                btn.addEventListener('click', async function() {
                    const userId = this.dataset.userId || this.getAttribute('data-user-id');
                    const currentlyBanned = this.dataset.banned === '1';
                    if (!userId) return;
                    this.disabled = true;

                    // For user we use explicit 'unban' or default ban
                    const action = currentlyBanned ? 'unban' : null;

                    const result = await doBanAction('user', userId, action);

                    if (result.ok && result.json) {
                        const raw = result.json;
                        // normalize: server might return { status, message, data: { is_banned } }
                        const data = raw.data ?? raw;
                        const isNowBanned = typeof data.is_banned !== 'undefined' ? !!data.is_banned : !currentlyBanned;

                        // update dataset + text + color
                        this.dataset.banned = isNowBanned ? '1' : '0';
                        this.textContent = isNowBanned ? '{{ __('messages.admin.unban') ?? 'Unban' }}' : '{{ __('messages.admin.ban') ?? 'Ban' }}';
                        this.style.background = isNowBanned ? '#ef4444' : '#6b7280';

                        // optionally update counts if provided
                        if (data.ops_count !== undefined) {
                            const el = document.getElementById('ops-count-' + userId);
                            if (el) el.textContent = data.ops_count;
                        }
                        if (data.msgs_count !== undefined) {
                            const el2 = document.getElementById('msgs-count-' + userId);
                            if (el2) el2.textContent = data.msgs_count;
                        }

                        // show server message (if present) otherwise generic
                        showToast((raw.message && String(raw.message).length) ? raw.message : (isNowBanned ? '{{ __("messages.ban.banned_now") ?? "Banned" }}' : '{{ __("messages.ban.unbanned") ?? "Unbanned" }}'), 'success');
                    } else {
                        // failure
                        const msg = (result.json && (result.json.message || (result.json.errors ? (Object.values(result.json.errors).flat().join(', ')) : null))) || '{{ __("messages.admin.server_error") ?? "Server error" }}';
                        showToast(msg, 'error');
                    }

                    this.disabled = false;
                });
            });

            /* -------- Avatar preview (kept) -------- */
            document.getElementById('avatarInput')?.addEventListener('change', function(e) {
                const file = this.files?.[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function(ev) {
                    const cardImg = document.querySelector('.avatar-large');
                    if (cardImg) {
                        cardImg.src = ev.target.result;
                    } else {
                        const initials = document.querySelector('.initials');
                        if (initials) {
                            const img = document.createElement('img');
                            img.className = 'avatar-large mx-auto d-block';
                            img.src = ev.target.result;
                            initials.replaceWith(img);
                        }
                    }
                };
                reader.readAsDataURL(file);
            });

            /* -------- Phone flow (kept) -------- */
            (function() {
                const csrf = csrfToken;
                const userId = @json($user->id ?? null);

                const stepPhone = document.getElementById('stepPhone');
                const stepCode = document.getElementById('stepCode');
                const phoneInput = document.getElementById('phoneInput');
                const phoneError = document.getElementById('phoneError');
                const btnSendPhone = document.getElementById('btnSendPhone');

                const codeInput = document.getElementById('codeInput');
                const codeError = document.getElementById('codeError');
                const btnVerifyCode = document.getElementById('btnVerifyCode');
                const btnBackToPhone = document.getElementById('btnBackToPhone');

                function setLoading(btn, loading) {
                    if (!btn) return;
                    if (loading) {
                        btn.dataset.orig = btn.innerHTML;
                        btn.innerHTML = '…';
                        btn.disabled = true;
                    } else {
                        btn.innerHTML = btn.dataset.orig || btn.innerHTML;
                        btn.disabled = false;
                    }
                }

                btnSendPhone?.addEventListener('click', function() {
                    if (!phoneInput) return;
                    phoneError.classList.add('d-none');
                    phoneError.textContent = '';
                    const phone = phoneInput.value.trim();
                    if (!phone) {
                        phoneError.textContent = '{{ __('messages.users.phone_required') ?? 'Phone required' }}';
                        phoneError.classList.remove('d-none');
                        return;
                    }

                    setLoading(this, true);
                    fetch("{{ route('telegram.sendPhone') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                phone: phone,
                                user_id: userId
                            })
                        })
                        .then(async res => {
                            setLoading(btnSendPhone, false);
                            if (!res.ok) {
                                const json = await res.json().catch(() => null);
                                if (json && json.errors && json.errors.phone) {
                                    phoneError.textContent = json.errors.phone.join(', ');
                                    phoneError.classList.remove('d-none');
                                } else {
                                    showToast(json?.message || 'Server error', 'error');
                                }
                                throw new Error('sendPhone failed');
                            }
                            return res.json();
                        })
                        .then(data => {
                            if (data.status === 'sms_sent' || data.status === 'sent') {
                                showToast(data.message || '{{ __('messages.users.sms_sent') ?? 'SMS sent' }}',
                                    'success');
                                stepPhone.classList.add('d-none');
                                stepCode.classList.remove('d-none');
                            } else {
                                showToast(data.message ||
                                    '{{ __('messages.users.sms_failed') ?? 'Failed to send' }}', 'error');
                            }
                        })
                        .catch(err => console.error(err));
                });

                btnVerifyCode?.addEventListener('click', function() {
                    if (!codeInput || !phoneInput) return;
                    codeError.classList.add('d-none');
                    codeError.textContent = '';
                    const phone = phoneInput.value.trim();
                    const code = codeInput.value.trim();
                    if (!phone || !code) {
                        codeError.textContent =
                            '{{ __('messages.users.code_required') ?? 'Phone and code required' }}';
                        codeError.classList.remove('d-none');
                        return;
                    }

                    setLoading(this, true);
                    fetch("{{ route('telegram.sendCode') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                phone: phone,
                                code: code,
                                user_id: userId
                            })
                        })
                        .then(async res => {
                            setLoading(btnVerifyCode, false);
                            if (!res.ok) {
                                const json = await res.json().catch(() => null);
                                if (json && json.errors) {
                                    if (json.errors.code) codeError.textContent = json.errors.code.join(', ');
                                    if (json.errors.phone) codeError.textContent += ' ' + json.errors.phone.join(', ');
                                    codeError.classList.remove('d-none');
                                } else {
                                    showToast(json?.message || 'Server error', 'error');
                                }
                                throw new Error('verify failed');
                            }
                            return res.json();
                        })
                        .then(data => {
                            if (data.status === 'verified') {
                                showToast(data.message || '{{ __('messages.users.verified') ?? 'Verified' }}',
                                    'success');
                                setTimeout(() => location.reload(), 800);
                            } else {
                                showToast(data.message ||
                                    '{{ __('messages.users.verify_failed') ?? 'Verification failed' }}',
                                    'error');
                            }
                        })
                        .catch(err => console.error(err));
                });

                btnBackToPhone?.addEventListener('click', function(e) {
                    e.preventDefault();
                    stepCode.classList.add('d-none');
                    stepPhone.classList.remove('d-none');
                });
            })();

            /* -------- Delete phone modal logic -------- */
            (function() {
                const backdrop = document.getElementById('modalBackdrop');
                const cancelBtn = document.getElementById('modalCancel');
                const modalBody = document.getElementById('modalBody');
                const modalForm = document.getElementById('modalDeleteForm');

                function openModal(route, phone) {
                    modalForm.setAttribute('action', route);
                    // put friendly phone text in modal
                    modalBody.textContent = "{{ __('messages.users.phone_delete_confirm') ?? 'Are you sure you want to delete this phone?' }}" + " — " + (phone || '');
                    backdrop.style.display = 'flex';
                    backdrop.setAttribute('aria-hidden', 'false');
                }

                function closeModal() {
                    backdrop.style.display = 'none';
                    backdrop.setAttribute('aria-hidden', 'true');
                    modalForm.setAttribute('action', '');
                }

                // attach to buttons
                document.querySelectorAll('.delete-phone-btn').forEach(btn => {
                    if (btn.dataset.bound === '1') return;
                    btn.dataset.bound = '1';
                    btn.addEventListener('click', function(e) {
                        const route = this.dataset.route;
                        const phone = this.dataset.phone;
                        if (!route) return;
                        openModal(route, phone);
                    });
                });

                cancelBtn?.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeModal();
                });

                // close on backdrop click (but not when clicking inside modal)
                backdrop?.addEventListener('click', function(e) {
                    if (e.target === backdrop) {
                        closeModal();
                    }
                });

                // escape key closes modal
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && backdrop.style.display === 'flex') {
                        closeModal();
                    }
                });
            })();

        });
    </script>
@endsection
