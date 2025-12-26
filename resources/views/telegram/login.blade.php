<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Telegram Login — Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        body { background:#0b1220; color:#e6eef6; font-family:Inter, system-ui, sans-serif; }
        .auth-box { max-width:420px; width:100%; background:#071427; padding:28px; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.6); }
        .form-label { color:#9fb7dd; }
        .form-control { background:#071827; color:#e7f4ff; border:1px solid rgba(255,255,255,0.04); }
        .form-control:focus { border-color:#3b82f6; box-shadow:0 0 0 0.15rem rgba(59,130,246,.12); }
        .btn-primary { background:#3b82f6; border:none; }
        .spinner { width:18px; height:18px; border:3px solid #fff; border-top-color:transparent; border-radius:50%; animation:spin .6s linear infinite; display:inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .alert { border-radius:8px; padding:8px 12px; }
    </style>
</head>

<body>
<div class="d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="auth-box">
        <h4 class="text-center mb-3">Telegram bilan bog'lash</h4>

        <div id="alertSuccess" class="alert alert-success d-none" role="alert"></div>
        <div id="alertError" class="alert alert-danger d-none" role="alert"></div>
        <div id="alertNeutral" class="alert alert-secondary d-none" role="alert"></div>

        <!-- Step 1: Phone Input -->
        <div id="stepPhone">
            <label class="form-label">Phone Number</label>
            <input id="phone" type="text" class="form-control mb-3" value="{{ old('phone', '') }}">
            <div id="phoneError" class="text-danger small d-none"></div>
            <button id="btnPhone" class="btn btn-primary w-100">Send SMS</button>
        </div>

        <!-- Step 2: Code Input -->
        <div id="stepCode" class="d-none mt-3">
            <label class="form-label">SMS Code</label>
            <input id="code" type="text" class="form-control mb-3" placeholder="Enter SMS code">
            <div id="codeError" class="text-danger small d-none"></div>
            <button id="btnCode" class="btn btn-primary w-100">Verify Code</button>
        </div>
    </div>
</div>

<script>
(function(){
    const userId = @json($userId ?? null); // PHP dan kelgan user_id yoki null
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // helperlar
    function showSuccess(msg){ const el=document.getElementById('alertSuccess'); el.innerText=msg; el.classList.remove('d-none'); setTimeout(()=>el.classList.add('d-none'),3500); }
    function showError(msg){ const el=document.getElementById('alertError'); el.innerText=msg; el.classList.remove('d-none'); setTimeout(()=>el.classList.add('d-none'),5000); }
    function showNeutral(msg){ const el=document.getElementById('alertNeutral'); el.innerText=msg; el.classList.remove('d-none'); }
    function hideNeutral(){ document.getElementById('alertNeutral').classList.add('d-none'); }

    function loading(btn,state){
        if(state){ btn.dataset.original = btn.innerHTML; btn.innerHTML = '<span class="spinner"></span>'; btn.disabled = true; }
        else { btn.innerHTML = btn.dataset.original || 'OK'; btn.disabled = false; }
    }

    // sendPhone
    document.getElementById('btnPhone').addEventListener('click', function(){
        const phone = document.getElementById('phone').value.trim();
        const phoneError = document.getElementById('phoneError');
        phoneError.classList.add('d-none'); phoneError.innerText = '';

        if(!phone){ phoneError.innerText = 'Telefon kiritilishi shart'; phoneError.classList.remove('d-none'); return; }

        const btn = this;
        loading(btn, true);

        fetch("{{ route('telegram.sendPhone') }}", {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
            body: JSON.stringify({ phone: phone, user_id: userId })
        })
        .then(async res => {
            loading(btn,false);
            if (!res.ok) {
                const json = await res.json().catch(()=>null);
                if (json && json.errors) {
                    // validation errors
                    if (json.errors.phone) {
                        phoneError.innerText = json.errors.phone.join(', ');
                        phoneError.classList.remove('d-none');
                    } else {
                        showError(json.message || 'Xatolik yuz berdi');
                    }
                } else {
                    const txt = await res.text().catch(()=>null);
                    showError(json?.message || txt || 'Server xatosi');
                }
                throw new Error('sendPhone failed');
            }
            return res.json();
        })
        .then(data => {
            if (data.status === 'sms_sent') {
                showSuccess(data.message || 'SMS yuborildi');
                // show code step
                document.getElementById('stepPhone').classList.add('d-none');
                document.getElementById('stepCode').classList.remove('d-none');
            } else {
                showError(data.message || 'Xato');
            }
        })
        .catch(err => console.error(err));
    });

    // sendCode
    document.getElementById('btnCode').addEventListener('click', function(){
        const phone = document.getElementById('phone').value.trim();
        const code = document.getElementById('code').value.trim();
        const codeError = document.getElementById('codeError');
        codeError.classList.add('d-none'); codeError.innerText = '';

        if(!phone || !code){
            codeError.innerText = 'Telefon va kod kiritilishi kerak';
            codeError.classList.remove('d-none');
            return;
        }

        const btn = this;
        loading(btn, true);
        showNeutral('Tasdiqlanmoqda, kuting...');

        fetch("{{ route('telegram.sendCode') }}", {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
            body: JSON.stringify({ phone: phone, code: code, user_id: userId })
        })
        .then(async res => {
            loading(btn,false);
            hideNeutral();
            if (!res.ok) {
                const json = await res.json().catch(()=>null);
                if (json && json.errors) {
                    // validation errors
                    let msg = '';
                    if (json.errors.code) msg = json.errors.code.join(', ');
                    if (json.errors.phone) msg += ' ' + json.errors.phone.join(', ');
                    codeError.innerText = msg || (json.message || 'Xatolik');
                    codeError.classList.remove('d-none');
                } else {
                    const txt = await res.text().catch(()=>null);
                    showError(json?.message || txt || 'Server xatosi');
                }
                throw new Error('sendCode failed');
            }
            return res.json();
        })
        .then(data => {
            if (data.status === 'verified') {
                showSuccess(data.message || 'Telegram tasdiqlandi');
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.history.back();
                }
            } else {
                showError(data.message || 'Tasdiqlash muvaffaqiyatsiz');
            }
        })
        .catch(err => console.error(err));
    });
})();
</script>
</body>
</html>
