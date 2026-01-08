@extends('layouts.app')

@section('title', __('messages.operations.title') . ' — ' . ($department->name ?? ''))
@section('page-title', $department->name . ' — ' . __('messages.operations.title'))

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ===== status badges & contrast improvements (override) ===== */
.status-badge {
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:6px 10px;
  border-radius:999px;
  font-weight:700;
  font-size:0.9rem;
  color:#0b1220;
}

/* colors (background + text) */
.status-sent { background: #bbf7d0; color: #064e3b; }        /* greenish */
.status-failed { background: #fecaca; color: #7f1d1d; }      /* red */
.status-canceled { background: #e9d5ff; color: #5b21b6; }   /* purple */
.status-scheduled { background: #fef3c7; color: #92400e; }  /* yellow */
.status-pending { background: #dbeafe; color: #1e3a8a; }    /* blue */

/* message card variants */
.message-group { background: var(--card-2); border: 1px solid rgba(255,255,255,0.04); border-radius:12px; padding:12px; }
.message-text { background: var(--card-3); border-left:4px solid var(--accent); padding:12px; border-radius:8px; color:var(--text); }

/* peer row */
.peer-row { background: rgba(255,255,255,0.02); border-radius:8px; padding:8px; }

/* small muted fix */
.text-muted.small { color: var(--muted) !important; }

/* toast / alert */
.alert {
  padding:10px 14px;
  border-radius:10px;
  margin-bottom:12px;
  font-weight:700;
}
.alert-success { background:#10b981; color:#04281b; }
.alert-error { background:#ef4444; color:#fff; }

/* compact status chip inside peer */
.status-chip {
  padding:4px 8px; border-radius:8px; font-weight:700; font-size:0.85rem;
  background: rgba(255,255,255,0.02);
}
</style>

<div class="container">

  {{-- SESSION FLASH --}}
  @if(session('success'))
    <div class="alert alert-success">
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-error">
      {{ session('error') }}
    </div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">{{ __('messages.operations.title') }}</h4>
      <div class="text-muted small">{{ __('messages.operations.subtitle', ['dept' => $department->name ]) }}</div>
    </div>

    <div class="d-flex gap-2 align-items-center">
      <form method="GET" class="d-flex gap-2 align-items-center">
        <input name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" type="search" placeholder="{{ __('messages.operations.search_placeholder') }}" style="width:240px;">
        <select name="status" class="form-select form-select-sm">
          <option value="">{{ __('messages.operations.filter_all_status') }}</option>
          <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>{{ __('messages.operations.status_pending') }}</option>
          <option value="scheduled" {{ ($status ?? '') === 'scheduled' ? 'selected' : '' }}>{{ __('messages.operations.status_scheduled') }}</option>
          <option value="sent" {{ ($status ?? '') === 'sent' ? 'selected' : '' }}>{{ __('messages.operations.status_sent') }}</option>
          <option value="canceled" {{ ($status ?? '') === 'canceled' ? 'selected' : '' }}>{{ __('messages.operations.status_canceled') }}</option>
          <option value="failed" {{ ($status ?? '') === 'failed' ? 'selected' : '' }}>{{ __('messages.operations.status_failed') }}</option>
        </select>

        <input type="date" name="from" value="{{ $from ?? '' }}" class="form-control form-control-sm" title="{{ __('messages.operations.filter_from') }}" />
        <input type="date" name="to" value="{{ $to ?? '' }}" class="form-control form-control-sm" title="{{ __('messages.operations.filter_to') }}" />

        <button class="btn btn-sm btn-primary" type="submit">{{ __('messages.operations.btn_search') }}</button>
      </form>
    </div>
  </div>

  {{-- Totals --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card p-3 text-center">
        <div class="text-muted small">{{ __('messages.operations.total_groups') }}</div>
        <h3 class="mb-0">{{ number_format($messageGroupsTotal ?? 0) }}</h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center">
        <div class="text-muted small">{{ __('messages.operations.total_messages') }}</div>
        <h3 class="mb-0">{{ number_format($telegramMessagesTotal ?? 0) }}</h3>
      </div>
    </div>
    <div class="col-md-6 text-end small-muted align-self-center">
      {{ __('messages.operations.showing') }} <strong>{{ $messageGroups->count() }}</strong> / {{ $messageGroups->total() }}
    </div>
  </div>

  {{-- Message groups list --}}
  <div class="card p-3 mb-3">
    @foreach ($messageGroups as $group)
      @php
        $gid = $group->id;
        $stat = $textStats->get($gid);
        $peers = $peerStatusByGroup[$gid] ?? [];
        $total = $groupTotals[$gid] ?? [];
        $sample = $stat->sample_text ?? null;
      @endphp

      <div class="mb-3 message-group">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div style="font-weight:800;">{{ __('messages.operations.group') }} #{{ $gid }}</div>
            <div class="text-muted small">
              {{ __('messages.operations.by_user') }}:
              {{ optional($group->phone->user)->name ?? '—' }}
              ({{ optional($group->phone)->phone ?? '—' }})
            </div>
          </div>

          <div style="display:flex; gap:8px;">
            <form method="POST" action="{{ route('message-groups.refresh', $gid) }}">
              @csrf
              <button type="submit" class="btn btn-sm btn-outline-info js-confirm-action" data-text="{{ __('messages.operations.confirm_refresh_text', ['id' => $gid]) }}">
                {{ __('messages.operations.btn_refresh') }}
              </button>
            </form>

            <form method="POST" action="{{ route('message-groups.cancel', $gid) }}">
              @csrf
              <button type="submit" class="btn btn-sm btn-outline-danger js-confirm-action" data-text="{{ __('messages.operations.confirm_cancel_text', ['id' => $gid]) }}">
                {{ __('messages.operations.btn_cancel') }}
              </button>
            </form>
          </div>
        </div>

        <hr style="border-color:rgba(255,255,255,0.03); margin:8px 0;">

        <div class="message-text">
          <strong style="color:var(--accent);">{{ __('messages.operations.text_label') }}:</strong>
          <span style="font-weight:600;">{{ $sample ?? '—' }}</span>
        </div>

        <div style="margin-top:8px;">
          <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:8px;">
            @foreach (['sent','failed','canceled','scheduled','pending'] as $k)
              @php $c = $total[$k] ?? 0; @endphp
              @if($c > 0)
                <span class="status-badge status-{{ $k }}">
                  {!! $k === 'sent' ? '✓' : ($k === 'failed' ? '✕' : ($k === 'canceled' ? '⦸' : '⏳')) !!}
                  <span style="opacity:.9;">{{ __('messages.operations.status_'.$k) }}</span>
                  <span class="ms-1" style="font-weight:900;">{{ $c }}</span>
                </span>
              @endif
            @endforeach
          </div>

          {{-- Peers list (scrollable) --}}
          <div style="max-height:220px; overflow:auto; padding-right:6px;">
            @foreach($peers as $peer => $statuses)
              @php $peerTotal = array_sum($statuses); @endphp
              <div class="peer-row d-flex justify-content-between align-items-center mb-2">
                <div style="min-width:0; overflow:hidden;">
                  <strong style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:300px;">{{ $peer }}</strong>
                  <div class="text-muted small">{{ __('messages.operations.peer_total') }}: {{ $peerTotal }}</div>
                </div>

                <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                  @foreach(['sent','failed','canceled','scheduled','pending'] as $kk)
                    @php $cnt = $statuses[$kk] ?? 0; @endphp
                    @if($cnt > 0)
                      <div class="status-chip">
                        {!! $kk === 'sent' ? '✓' : ($kk === 'failed' ? '✕' : ($kk === 'canceled' ? '⦸' : '⏳')) !!}
                        <span style="opacity:.9;">{{ __('messages.operations.status_'.$kk) }}</span>
                        <strong style="color:var(--accent); margin-left:6px;">{{ $cnt }}</strong>
                      </div>
                    @endif
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>

          {{-- totals / rate --}}
          @php
            $all = array_sum($total);
            $sent = $total['sent'] ?? 0;
            $rate = $all > 0 ? round(($sent / $all) * 100) : 0;
          @endphp

          <div style="margin-top:8px; display:flex; gap:12px; flex-wrap:wrap; font-weight:700;">
            <div>{{ __('messages.operations.total') }}: <span style="color:var(--accent)">{{ $all }}</span></div>
            <div>{{ __('messages.operations.total_sent') }}: <span style="color:#22c55e">{{ $sent }}</span></div>
            <div>{{ __('messages.operations.rate') }}: <span style="color:var(--accent2)">{{ $rate }}%</span></div>
          </div>
        </div>
      </div>
    @endforeach

    <div class="mt-3 d-flex justify-content-center">
      {{ $messageGroups->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  </div>
</div>

{{-- Confirm modal (reused) --}}
<div id="confirmOverlay" style="display:none; position:fixed; inset:0; align-items:center; justify-content:center; z-index:99998;">
  <div style="width:100%; max-width:520px; background:var(--card); color:var(--text); border-radius:12px; padding:18px; box-shadow:0 20px 60px rgba(0,0,0,.6);">
    <h5 id="confirmTitle">{{ __('messages.operations.confirm') }}</h5>
    <p id="confirmDesc" class="text-muted"></p>

    <div id="confirmStep1" class="d-flex justify-content-end gap-2">
      <button id="confirmCancel" class="btn btn-sm btn-secondary">{{ __('messages.operations.cancel') }}</button>
      <button id="confirmContinue" class="btn btn-sm btn-primary">{{ __('messages.operations.continue') }}</button>
    </div>
  </div>
</div>

<script>
(function(){
  const overlay = document.getElementById('confirmOverlay');
  const desc = document.getElementById('confirmDesc');
  const cancel = document.getElementById('confirmCancel');
  const cont = document.getElementById('confirmContinue');

  let activeForm = null;

  function openConfirm(text, form) {
    activeForm = form;
    desc.textContent = text || '{{ __("messages.operations.confirm_text_default") }}';
    overlay.style.display = 'flex';
  }

  function closeConfirm() {
    overlay.style.display = 'none';
    activeForm = null;
  }

  cancel.addEventListener('click', closeConfirm);
  cont.addEventListener('click', function(){
    if (!activeForm) return closeConfirm();
    activeForm.submit();
  });

  // attach to js-confirm-action buttons (forms contain .js-confirm-action button)
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.js-confirm-action');
    if (!btn) return;
    e.preventDefault();

    // find enclosing form
    const form = btn.closest('form');
    const txt = btn.getAttribute('data-text') || btn.dataset.text || '';
    openConfirm(txt, form);
  });
})();
</script>

@endsection
