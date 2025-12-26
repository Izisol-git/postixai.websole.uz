<!DOCTYPE html>
<html lang="uz">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>POSTIX AI</title>

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
            max-width: 920px;
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
            padding: 18px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.6);
            margin-bottom: 14px;
        }

        .card h3 {
            color: var(--yellow);
            margin-top: 0;
            margin-bottom: 10px;
        }

        /* Form */
        .form-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .form-group {
            flex: 1 1 300px;
            min-width: 240px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        label {
            font-size: 0.95rem;
            color: var(--muted);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            background: #071827;
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.95rem;
            outline: none;
        }

        input:focus,
        select:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
            border-color: rgba(59, 130, 246, 0.9);
        }

        .help {
            color: var(--muted);
            font-size: 0.88rem;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 8px;
        }

        .btn {
            padding: 8px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: #16a34a;
            /* green */
            color: #fff;
        }

        .btn-secondary {
            background: var(--card);
            color: var(--muted);
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        /* error list */
        .errors {
            background: rgba(239, 68, 68, 0.06);
            border: 1px solid rgba(239, 68, 68, 0.12);
            color: var(--danger);
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .field-error {
            color: #ffb4b4;
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .note {
            color: var(--muted);
            font-size: 0.9rem;
            margin-top: 6px;
        }

        @media (max-width:760px) {
            .form-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <div>
                <div class="title">Foydalanuvchi yaratish</div>
                <div class="breadcrumbs">Admin panel / Foydalanuvchilar / Yangi</div>
            </div>

            <div style="display:flex; gap:8px; align-items:center;">
                <a href="{{ url()->previous() }}" class="btn btn-secondary" style="text-decoration:none;">
                        ← Back
                    </a>
            </div>
        </div>

        <div class="card">
            <h3>Yangi foydalanuvchi qo'shish</h3>

            {{-- Global errors --}}
            @if ($errors->any())
                <div class="errors">
                    <strong>Xatoliklar:</strong>
                    <ul style="margin:8px 0 0 16px; padding:0;">
                        @foreach ($errors->all() as $error)
                            <li style="margin-bottom:6px;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Ism (name) <span style="color:var(--danger)">*</span></label>
                        <input id="name" name="name" type="text"  required 
                            autocomplete="name" />
                        @error('name')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="telegram_id">Telegram ID (telegram_id) <span
                                style="color:var(--danger)">*</span></label>
                        <input id="telegram_id" name="telegram_id" type="text" required
                            placeholder="123412345" autocomplete="off" />
                        <div class="help">Foydalanuvchining Telegram raqami yoki ID</div>
                        @error('telegram_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="department_id">Bo'lim (department) <span
                                style="color:var(--danger)">*</span></label>
                        <select id="department_id" name="department_id" required>
                            <option value="">— Tanlang —</option>
                            @if (isset($departments) && $departments->count())
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            @else
                                <option value="">No departments available</option>
                            @endif
                        </select>
                        @error('department_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="role_id">Rol (role) <span style="color:var(--danger)">*</span></label>
                        <select id="role_id" name="role_id" required>
                            <option value="">— Tanlang —</option>
                            @if (isset($roles) && $roles->count())
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}"
                                        data-role-name="{{ $role->name }}"
                                        {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            @else
                                <option value="">No roles available</option>
                            @endif
                        </select>
                        @error('role_id')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Email & Password block (conditional) -->
                <div id="credential-block" style="display:none; margin-top:12px;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email <span style="color:var(--danger)">*</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" />
                            @error('email')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Parol (password) <span style="color:var(--danger)">*</span></label>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <input id="password" name="password" type="password" style="flex:1;" />
                                <button type="button" id="togglePassword" class="btn btn-secondary"
                                    style="padding:6px 10px;">Show</button>
                            </div>
                            <div class="help">Minimal xavfsiz parol qoida (server validator bo'lishi kerak)</div>
                            @error('password')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create user</button>
                    
                <a href="{{ route('departments.index') }}" class="btn btn-secondary" style="text-decoration:none;">Cancel</a>


                    
                </div>
            </form>
        </div>

        <div class="note">
            <strong>Note:</strong> Barcha maydonlar majburiy. Server tomonidan ham validatsiya o'tadi.
        </div>
    </div>

    <script>
        (function() {
            // Password toggle
            const pw = document.getElementById('password');
            const toggleBtn = document.getElementById('togglePassword');
            if (pw && toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    if (pw.type === 'password') {
                        pw.type = 'text';
                        toggleBtn.textContent = 'Hide';
                    } else {
                        pw.type = 'password';
                        toggleBtn.textContent = 'Show';
                    }
                });
            }

            // Fill sample data
            const fillBtn = document.getElementById('fillTest');
            if (fillBtn) {
                fillBtn.addEventListener('click', function() {
                    document.getElementById('name').value = 'Test User';
                    document.getElementById('telegram_id').value = '123456789';
                    const dept = document.getElementById('department_id');
                    if (dept && dept.options.length > 1) dept.selectedIndex = 1;
                    const role = document.getElementById('role_id');
                    if (role && role.options.length > 1) role.selectedIndex = 1;
                    document.getElementById('email').value = 'test+' + Date.now() + '@example.com';
                    document.getElementById('password').value = 'Secret123!';
                    triggerRoleChange(); // show credentials if needed
                });
            }

            // Conditional show/hide credential block
            const roleSelect = document.getElementById('role_id');
            const credBlock = document.getElementById('credential-block');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            function shouldShowCredentials(roleName) {
                if (!roleName) return false;
                roleName = roleName.toLowerCase();
                return roleName === 'admin' || roleName === 'superadmin';
            }

            function applyVisibility() {
                const opt = roleSelect.options[roleSelect.selectedIndex];
                const roleName = opt?.dataset?.roleName || '';
                if (shouldShowCredentials(roleName)) {
                    credBlock.style.display = '';
                    if (emailInput) emailInput.setAttribute('required', 'required');
                    if (passwordInput) passwordInput.setAttribute('required', 'required');
                } else {
                    credBlock.style.display = 'none';
                    if (emailInput) emailInput.removeAttribute('required');
                    if (passwordInput) passwordInput.removeAttribute('required');
                }
            }

            function triggerRoleChange() {
                applyVisibility();
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', applyVisibility);
            }

            document.addEventListener('DOMContentLoaded', applyVisibility);
        })();
    </script>
</body>



</html>
