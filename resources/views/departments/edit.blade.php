<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Department tahrirlash — Postix Ai</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root { 
    --bg:#071427; 
    --card:#0f2233; 
    --muted:#9fb7dd; 
    --text:#e7f4ff; 
    --accent:#3b82f6; 
    --yellow:#facc15; 
}

body { 
    background:var(--bg); 
    color:var(--text); 
    font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial; 
    padding:20px; 
}

.container { 
    max-width:900px; 
    margin:0 auto; 
}

/* Topbar */
.topbar { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    margin-bottom:18px; 
    gap:12px; 
}

.title { 
    font-size:1.25rem; 
    font-weight:700; 
    color:var(--text); 
    display:flex; 
    gap:8px; 
    align-items:center; 
}

.breadcrumbs { 
    color:var(--muted); 
    font-size:0.95rem; 
}

.right-controls { 
    display:flex; 
    gap:10px; 
    align-items:center; 
}

/* Card */
.card { 
    background:var(--card); 
    border-radius:12px; 
    padding:18px; 
    border:1px solid rgba(255,255,255,0.03); 
    box-shadow:0 8px 30px rgba(0,0,0,0.6); 
}

.card h3 { 
    color: var(--yellow); 
    margin-top:0; 
    margin-bottom:6px; 
}

/* Form */
.form-label { 
    color:var(--muted); 
}

.form-control { 
    background: #ffffff; 
    color: var(--text); 
    border-radius:10px; 
    border:1px solid rgba(255,255,255,0.04); 
    height:46px; 
    padding:10px; 
}

.btn-save { 
    background:var(--accent); 
    color:white; 
    border-radius:10px; 
    padding:8px 14px; 
    border:none; 
    font-weight:700; 
}

.btn-cancel { 
    background:transparent; 
    color:var(--muted); 
    border:1px solid rgba(255,255,255,0.04); 
    padding:7px 12px; 
    border-radius:10px; 
}

/* Logout button */
.logout-btn {
    background-color: #ef4444; /* qizil fon */
    color: white;              /* matn oq */
    border-radius: 8px;
    border: none;
    padding: 6px 12px;
    cursor: pointer;
}

/* Alerts */
.alert { 
    border-radius:10px; 
}

/* Helper */
.small-note { 
    color:var(--muted); 
    font-size:0.9rem; 
    margin-top:8px; 
}
</style>
</head>
<body>
<div class="container">

  <!-- Top bar -->
  <div class="topbar">
    <div class="title">
        <span style="font-weight:800; color:var(--yellow);">POSTIX AI</span>
        <span class="breadcrumbs">
            <a href="{{ route('departments.index') }}" style="color:var(--muted); text-decoration:none;">Departments</a> → Tahrirlash
        </span>
    </div>

    <div class="right-controls">
        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
  </div>

  <!-- Card -->
  <div class="card">
    <h3>Department tahrirlash</h3>

    <!-- Success message -->
    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Error messages -->
    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Department edit form -->
    <form action="{{ route('departments.update', $department->id) }}" method="POST" class="mt-3">
      @csrf
      @method('PUT')

      <div class="mb-3">
        <label class="form-label">Nomi</label>
        <input type="text" name="name" value="{{ old('name', $department->name) }}" class="form-control" placeholder="Department nomi" required>
        <div class="small-note">Masalan: Marketing, Sales, Support — qisqa va tushunarli nom kiriting.</div>
      </div>

      <div class="d-flex gap-2 justify-content-end mt-4">
        <a href="{{ route('departments.index') }}" class="btn-cancel">Bekor qilish</a>
        <button type="submit" class="btn-save">Saqlash</button>
      </div>
    </form>
  </div>

</div>
</body>
</html>
