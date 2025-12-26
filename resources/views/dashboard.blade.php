<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>POSTIX AI - SuperAdmin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
:root { --bg:#071427; --card:#0f2233; --muted:#9fb7dd; --text:#e7f4ff; --accent:#3b82f6; --yellow:#facc15; }
body { background:var(--bg); color:var(--text); font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial; padding:20px; }
.container-max { max-width:1200px; margin:0 auto; }

/* Top */
.topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; }
.title { font-size:1.4rem; font-weight:700; color:var(--text); }
.right-controls { display:flex; gap:10px; align-items:center; }

/* Range buttons */
.range-filters { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; }
.range-btn { background:transparent; color:var(--muted); border:1px solid rgba(255,255,255,0.04); padding:6px 10px; border-radius:10px; cursor:pointer; }
.range-btn.active { color:var(--text); background:linear-gradient(90deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)); border-color: rgba(255,255,255,0.06); }

/* Departments grid (top) */
.grid-container { display:grid; grid-template-columns: repeat(auto-fit,minmax(260px,1fr)); gap:12px; margin-top:6px; margin-bottom:18px; }
.grid-card { background:transparent; border-radius:12px; padding:14px; border:2px solid var(--yellow); box-shadow:0 6px 20px rgba(0,0,0,0.6); }
.dept-title { font-weight:800; color:var(--text); margin-bottom:8px; font-size:1.06rem; }
.stat-row { display:flex; justify-content:space-between; padding:8px; border-radius:8px; margin-bottom:8px; background:linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.00)); }
.stat-label { color:var(--muted); font-size:0.9rem; }
.stat-val { font-weight:800; }

/* Batafsil link more visible */
.details-link { display:inline-block; margin-top:6px; font-size:0.95rem; color:var(--yellow); font-weight:800; text-decoration:underline; }

/* Pies row */
.pies-row { display:flex; gap:14px; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; margin-top:6px; }
.pie-card { background:var(--card); border-radius:12px; padding:12px; width:calc(25% - 10px); min-width:180px; box-shadow:0 8px 30px rgba(0,0,0,0.6); text-align:center; }
.pie-title { font-size:0.95rem; color:var(--muted); margin-bottom:8px; }
.pie-canvas { width:110px; height:110px; margin:0 auto; display:block; }
.pie-total { font-weight:700; margin-top:8px; font-size:1.05rem; color:var(--text); }
.legend { display:flex; flex-wrap:wrap; gap:8px; justify-content:center; margin-top:8px; }
.legend-item { display:flex; gap:8px; align-items:center; font-size:0.82rem; color:var(--muted); opacity:0.95; cursor:pointer; }
.legend-dot { width:12px; height:12px; border-radius:3px; display:inline-block; }

/* Create button right below top */
.create-area { display:flex; justify-content:flex-end; margin-bottom:8px; }
.btn-create { background:var(--accent); color:white; border-radius:10px; padding:8px 12px; border:none; font-weight:700; }

/* Responsive */
@media (max-width:1000px) { .pie-card { width:calc(50% - 10px); } }
@media (max-width:600px) { .pie-card { width:100%; } .grid-card { padding:12px; } }
</style>
</head>
<body>
<div class="container-max">

  <!-- Top -->
  <div class="topbar">
    <div class="title">SuperAdmin</div>
    <div class="right-controls">
      <a href="{{ route('departments.create') ?? '#' }}" class="btn-create">Department yaratish</a>

      <form action="{{ route('logout') }}" method="POST" style="margin:0;">
        @csrf
        <button class="range-btn" type="submit" style="background:#ef4444; color:white; border-radius:8px;">Logout</button>
      </form>

    </div>
  </div>

  <!-- Date filter -->
  <form id="rangeForm" method="GET" action="{{ route('departments.index') }}">
    <div class="range-filters">
      @php $ranges = ['all' => 'All', 'year' => 'Year', 'month' => 'Month', 'week' => 'Week', 'day' => 'Day']; @endphp
      @foreach($ranges as $k => $label)
        <button name="range" value="{{ $k }}" type="submit" class="range-btn {{ $range === $k ? 'active' : '' }}">{{ $label }}</button>
      @endforeach
    </div>
  </form>

  <!-- Departments (TOP) -->
  <div class="grid-container">
    @foreach($deptStats as $d)
      <div class="grid-card">
        <div class="dept-title">{{ $d->name }}</div>

        <div class="stat-row">
          <div class="stat-label">Foydalanuvchilar</div>
          <div class="stat-val">{{ $d->users_count }}</div>
        </div>

        <div class="stat-row">
          <div class="stat-label">Aktiv telefonlar</div>
          <div class="stat-val">{{ $d->active_phones_count }}</div>
        </div>

        <div class="stat-row" title="Operatsiya">
          <div class="stat-label">Operatsiya</div>
          <div class="stat-val">{{ $d->message_groups_count }}</div>
        </div>

        <div class="stat-row">
          <div class="stat-label">Habarlar soni</div>
          <div class="stat-val">{{ $d->telegram_messages_count }}</div>
        </div>

        <a href="{{ route('departments.show', $d->id) ?? '#' }}" class="details-link">Batafsil</a>
      </div>
    @endforeach
  </div>

  <!-- Create area placeholder (keeps spacing) -->
  <div class="create-area"></div>

  <!-- Pies (below departments) -->
  <div class="pies-row">
    {{-- Users --}}
    <div class="pie-card">
      <div class="pie-title">Foydalanuvchilar</div>
      <canvas id="chartUsers" class="pie-canvas"></canvas>
      <div class="pie-total" id="totalUsers">{{ $totals['users'] }}</div>
      <div class="legend" id="legendUsers">
        @php $i=0; @endphp
        @foreach($chartUsers as $name => $val)
          <div class="legend-item" data-index="{{ $i }}" data-name="{{ $name }}">
            <span class="legend-dot" style="background: {{ $colors[$i % count($colors)] }}"></span>
            <span>{{ $name }} ({{ $val }})</span>
          </div>
        @php $i++; @endphp
        @endforeach
      </div>
    </div>

    {{-- Phones --}}
    <div class="pie-card">
      <div class="pie-title">Aktiv telefonlar</div>
      <canvas id="chartPhones" class="pie-canvas"></canvas>
      <div class="pie-total" id="totalPhones">{{ $totals['phones'] }}</div>
      <div class="legend" id="legendPhones">
        @php $i=0; @endphp
        @foreach($chartPhones as $name => $val)
          <div class="legend-item" data-index="{{ $i }}" data-name="{{ $name }}">
            <span class="legend-dot" style="background: {{ $colors[$i % count($colors)] }}"></span>
            <span>{{ $name }} ({{ $val }})</span>
          </div>
        @php $i++; @endphp
        @endforeach
      </div>
    </div>

    {{-- Operatsiya --}}
    <div class="pie-card">
      <div class="pie-title">Operatsiya</div>
      <canvas id="chartGroups" class="pie-canvas"></canvas>
      <div class="pie-total" id="totalGroups">{{ $totals['groups'] }}</div>
      <div class="legend" id="legendGroups">
        @php $i=0; @endphp
        @foreach($chartGroups as $name => $val)
          <div class="legend-item" data-index="{{ $i }}" data-name="{{ $name }}">
            <span class="legend-dot" style="background: {{ $colors[$i % count($colors)] }}"></span>
            <span>{{ $name }} ({{ $val }})</span>
          </div>
        @php $i++; @endphp
        @endforeach
      </div>
    </div>

    {{-- Telegram messages --}}
    <div class="pie-card">
      <div class="pie-title">Umumiy Habarlar soni</div>
      <canvas id="chartMessages" class="pie-canvas"></canvas>
      <div class="pie-total" id="totalMessages">{{ $totals['messages'] }}</div>
      <div class="legend" id="legendMessages">
        @php $i=0; @endphp
        @foreach($chartMessages as $name => $val)
          <div class="legend-item" data-index="{{ $i }}" data-name="{{ $name }}">
            <span class="legend-dot" style="background: {{ $colors[$i % count($colors)] }}"></span>
            <span>{{ $name }} ({{ $val }})</span>
          </div>
        @php $i++; @endphp
        @endforeach
      </div>
    </div>
  </div>

</div>

<script>
/* Backend data -> JS */
const labels = {!! json_encode($chartUsers->keys()->toArray()) !!};
const usersData = {!! json_encode($chartUsers->values()->toArray()) !!};
const phonesData = {!! json_encode($chartPhones->values()->toArray()) !!};
const groupsData = {!! json_encode($chartGroups->values()->toArray()) !!};
const messagesData = {!! json_encode($chartMessages->values()->toArray()) !!};
const colors = {!! json_encode($colors) !!};

/* Hidden set */
let hidden = new Set();

function masked(arr) {
  return arr.map((v,i) => hidden.has(i) ? 0 : v);
}

const baseOpts = {
  type: 'pie',
  data: {},
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: {} }
    },
    onClick(evt, items) {
      if (!items.length) return;
      const idx = items[0].index;
      if (hidden.has(idx)) hidden.delete(idx); else hidden.add(idx);
      updateAll();
      // update legend opacity
      document.querySelectorAll('.legend-item').forEach(el => {
        const i = parseInt(el.dataset.index);
        el.style.opacity = hidden.has(i) ? 0.35 : 1.0;
      });
    }
  }
};

/* Create charts */
const ctxU = document.getElementById('chartUsers').getContext('2d');
const chartUsers = new Chart(ctxU, {
  ...baseOpts,
  data: { labels, datasets: [{ data: masked(usersData), backgroundColor: colors }] }
});

const ctxP = document.getElementById('chartPhones').getContext('2d');
const chartPhones = new Chart(ctxP, {
  ...baseOpts,
  data: { labels, datasets: [{ data: masked(phonesData), backgroundColor: colors }] }
});

const ctxG = document.getElementById('chartGroups').getContext('2d');
const chartGroups = new Chart(ctxG, {
  ...baseOpts,
  data: { labels, datasets: [{ data: masked(groupsData), backgroundColor: colors }] }
});

const ctxM = document.getElementById('chartMessages').getContext('2d');
const chartMessages = new Chart(ctxM, {
  ...baseOpts,
  data: { labels, datasets: [{ data: masked(messagesData), backgroundColor: colors }] }
});

function sum(arr){ return arr.reduce((a,b)=>a+(b||0),0); }

function updateAll(){
  chartUsers.data.datasets[0].data = masked(usersData); chartUsers.update();
  chartPhones.data.datasets[0].data = masked(phonesData); chartPhones.update();
  chartGroups.data.datasets[0].data = masked(groupsData); chartGroups.update();
  chartMessages.data.datasets[0].data = masked(messagesData); chartMessages.update();

  document.getElementById('totalUsers').innerText = sum(masked(usersData));
  document.getElementById('totalPhones').innerText = sum(masked(phonesData));
  document.getElementById('totalGroups').innerText = sum(masked(groupsData));
  document.getElementById('totalMessages').innerText = sum(masked(messagesData));
}

/* Legend click toggles too */
document.querySelectorAll('.legend-item').forEach(el=>{
  el.addEventListener('click', () => {
    const idx = parseInt(el.dataset.index);
    if (hidden.has(idx)) hidden.delete(idx); else hidden.add(idx);
    updateAll();
    el.style.opacity = hidden.has(idx) ? 0.35 : 1.0;
  });
});

/* fill legend dots colors */
document.querySelectorAll('.legend-dot').forEach((el, i)=>{
  el.style.background = colors[i % colors.length];
});

/* initial */
updateAll();
</script>
</body>
</html>
