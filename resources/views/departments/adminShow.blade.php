@extends('layouts.app')

@section('title', __('messages.admin.dashboard'))
@section('page-title', __('messages.admin.main_dashboard'))


@section('content')


<div class="mb-3 d-flex justify-content-between align-items-center">
  <div>
    <form id="rangeForm" method="GET" class="d-inline">
      <input type="hidden" name="range" id="rangeInput" value="{{ $range ?? 'year' }}">
      <div class="btn-group" role="group" aria-label="Range">
        <button type="button" class="btn btn-sm btn-outline-secondary {{ ($range ?? 'all') === 'all' ? 'active' : '' }}" onclick="setRange('all')">{{ __('messages.admin.all_time') }}</button>
        <button type="button" class="btn btn-sm btn-outline-secondary {{ ($range ?? 'year') === 'year' ? 'active' : '' }}" onclick="setRange('year')">{{ __('messages.admin.all_year') }}</button>
        <button type="button" class="btn btn-sm btn-outline-secondary {{ ($range ?? '') === 'month' ? 'active' : '' }}" onclick="setRange('month')">{{ __('messages.admin.month') }}</button>
        <button type="button" class="btn btn-sm btn-outline-secondary {{ ($range ?? '') === 'day' ? 'active' : '' }}" onclick="setRange('day')">{{ __('messages.admin.day') }}</button>
      </div>
    </form>
  </div>

  <div class="text-muted small">
    {{ __('messages.admin.users') }}: <strong>{{ number_format($usersCount) }}</strong>
    &nbsp; • &nbsp; {{ __('messages.admin.operations') }}: <strong>{{ number_format($messageGroupsCount) }}</strong>
  </div>
</div>

{{-- STATS CARDS --}}
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card text-center p-3">
      <h2 class="mb-1">{{ number_format($usersCount) }}</h2>
      <div class="text-muted">{{ __('messages.admin.users') }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center p-3">
      <h2 class="mb-1">{{ number_format($activePhonesCount) }}</h2>
      <div class="text-muted">{{ __('messages.admin.phones') }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center p-3">
      <h2 class="mb-1">{{ number_format($messageGroupsCount) }}</h2>
      <div class="text-muted">{{ __('messages.admin.operations') }}</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center p-3">
      <h2 class="mb-1">{{ number_format($telegramMessagesCount) }}</h2>
      <div class="text-muted">{{ __('messages.admin.messages_count') }}</div>
    </div>
  </div>
  
</div>

{{-- CHARTS --}}
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card p-3 mb-3">
      <h5>{{ __('messages.admin.messages_per_day') }} — <small class="text-muted">@if(($range ?? '') === 'all') {{ __('messages.admin.all_time') }} @else {{ __('messages.admin.'.$range) }} @endif</small></h5>
      <div style="height:320px;">
        <canvas id="chartMessagesPerDay"></canvas>
      </div>
    </div>

    <div class="card p-3">
      <h5>{{ __('messages.admin.grouped_bar') }}</h5>

      {{-- For many phones/users, enable horizontal scroll --}}
      <div id="phonesBarWrap" style="overflow:auto;">
        <div id="phonesBarInner" style="min-width:900px;padding:8px;">
          <canvas id="chartPhonesPerUser"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card p-3 mb-3">
      <h5>{{ __('messages.admin.users_by_operations') }}</h5>
      <div style="height:260px;">
        <canvas id="chartUsersOps"></canvas>
      </div>
    </div>

    <div class="card p-3">
      <h5>{{ __('messages.admin.last_active_users') }}</h5>
      <div class="mt-2">
        @if($lastActiveUsers->isEmpty())
          <div class="text-muted small">{{ __('messages.admin.no_recent_activity') }}</div>
        @else
          @foreach($lastActiveUsers as $u)
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <strong>{{ $u->name }}</strong>
                <div class="text-muted small">{{ $u->ops_count }} {{ __('messages.admin.operations') }} • {{ \Carbon\Carbon::parse($u->last_active)->diffForHumans() }}</div>
              </div>
              <span class="badge bg-secondary">{{ __('messages.admin.active') }}</span>
            </div>
          @endforeach
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
/* Range submit */
function setRange(r){
  document.getElementById('rangeInput').value = r;
  document.getElementById('rangeForm').submit();
}

/* Data from controller */
const labelsPerDay = {!! json_encode($messagesPerDayLabels ?? []) !!};
const valuesPerDay = {!! json_encode($messagesPerDayValues ?? []) !!};

const usersLabels = {!! json_encode($usersOpsLabels ?? []) !!};
const usersValues = {!! json_encode($usersOpsValues ?? []) !!};

/* phones per user: phoneLabels (labels for datasets), phoneDatasets (each has label + data array aligned to usersLabels order) */
const phoneLabels = {!! json_encode($phoneLabels ?? []) !!};
const phoneDatasets = {!! json_encode($phoneDatasets ?? []) !!};

/* Helpers */
function genColors(n){
  const arr=[];
  for(let i=0;i<n;i++){
    arr.push(`hsl(${(i*360)/Math.max(1,n)},70%,52%)`);
  }
  return arr;
}
function cssVar(name){ return getComputedStyle(document.body).getPropertyValue(name).trim() || '#3b82f6'; }

const charts = [];

/* Messages per period (line) */
const ctxM = document.getElementById('chartMessagesPerDay').getContext('2d');
charts.push(new Chart(ctxM, {
  type:'line',
  data:{
    labels: labelsPerDay,
    datasets:[{
      label: "{{ __('messages.admin.messages_per_day') }}",
      data: valuesPerDay,
      borderColor: cssVar('--accent'),
      backgroundColor: cssVar('--accent') + '33',
      tension: .35,
      fill:true,
      pointRadius:2
    }]
  },
  options:{
    maintainAspectRatio:false,
    plugins:{ legend:{ display:false } },
    scales:{ x:{ ticks:{ color: cssVar('--muted') } }, y:{ ticks:{ color: cssVar('--muted') }, beginAtZero:true } }
  }
}));

/* Doughnut: users by ops */
const ctxU = document.getElementById('chartUsersOps').getContext('2d');
charts.push(new Chart(ctxU, {
  type:'doughnut',
  data:{ labels: usersLabels, datasets:[ { data: usersValues, backgroundColor: genColors(usersLabels.length) } ] },
  options:{ maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ color: cssVar('--muted') } } } }
}));

/* PHONES PER USER grouped bar:
   - x-axis = usersLabels
   - datasets = phones (label = masked phone), data = array aligned to usersLabels
*/
(function(){
  const canvas = document.getElementById('chartPhonesPerUser');
  // compute width: make it wider if many users so bars aren't too thin
  const usersCount = Math.max(1, usersLabels.length);
  const phonesCount = Math.max(1, phoneLabels.length);

  // base width heuristics
  const basePerUser = 90; // px per user block
  const desiredWidth = Math.max(900, Math.round(usersCount * basePerUser));
  canvas.style.width = desiredWidth + 'px';
  canvas.style.height = '480px';

  // build datasets with colors
  const palette = genColors(phonesCount);
  const datasets = phoneDatasets.map((p, idx) => ({
    label: p.label,
    data: p.data,
    backgroundColor: palette[idx],
    borderColor: palette[idx],
    borderWidth: 1,
    barThickness: Math.max(8, Math.min(60, Math.floor((desiredWidth / usersCount) * 0.6 / Math.max(1, phonesCount))))
  }));

  const ctx = canvas.getContext('2d');
  charts.push(new Chart(ctx, {
    type: 'bar',
    data: {
      labels: usersLabels,
      datasets: datasets
    },
    options: {
      maintainAspectRatio:false,
      plugins: { legend: { position:'bottom', labels: { color: cssVar('--muted') } } },
      scales: {
        x: { ticks: { color: cssVar('--muted') }, stacked: false },
        y: { ticks: { color: cssVar('--muted') }, beginAtZero: true }
      }
    }
  }));
})();

/* update charts on theme changes */
const observer = new MutationObserver(() => charts.forEach(c => c.update()));
observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
</script>
@endsection
