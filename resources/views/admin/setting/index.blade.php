@extends('layout.app', ['page_name' => 'Settings'])
@push('styles')
<style>
/* ── Page background ──────────────────────────────────────────────── */
body { background: #f5f7fa !important; }

/* ── Layout ───────────────────────────────────────────────────────── */
.settings-layout { display: flex; gap: 1.5rem; align-items: flex-start; }
.settings-sidebar { width: 220px; flex-shrink: 0; position: sticky; top: 80px; }
.settings-content { flex: 1; min-width: 0; }
.settings-panel  { display: none; }
.settings-panel.active { display: block; }

/* ── Sidebar card ─────────────────────────────────────────────────── */
.s-sidebar-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    padding: .6rem .5rem;
}
.s-sidebar-card .nav-link {
    display: flex; align-items: center; gap: .6rem;
    padding: .6rem .9rem;
    border-radius: 8px;
    font-size: .875rem; font-weight: 500;
    color: #374151;
    border: none; background: transparent; width: 100%; text-align: left;
    transition: background .15s, color .15s;
    cursor: pointer;
}
.s-sidebar-card .nav-link i { font-size: .82rem; width: 16px; text-align: center; color: #9ca3af; }
.s-sidebar-card .nav-link:hover { background: #f0f4ff; color: #1d4ed8; }
.s-sidebar-card .nav-link:hover i { color: #1d4ed8; }
.s-sidebar-card .nav-link.active {
    background: #0f2745; color: #fff; font-weight: 600;
}
.s-sidebar-card .nav-link.active i { color: #fff; opacity: .9; }

/* ── Main content card (wraps QB panel) ───────────────────────────── */
.qb-main-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 2px 16px rgba(0,0,0,.07);
    padding: 2rem 2rem 2.5rem;
}

/* ── Two-column content area ──────────────────────────────────────── */
.qb-cols { display: flex; gap: 1.5rem; align-items: flex-start; }
.qb-steps { flex: 1; min-width: 0; }
.qb-checklist-col { width: 240px; flex-shrink: 0; }

/* ── Progress header ──────────────────────────────────────────────── */
.qb-progress-row {
    display: flex; align-items: center; justify-content: space-between;
    gap: 1.5rem; margin-bottom: 1.75rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
}
.qb-progress-left { flex: 1; }
.qb-progress-heading { font-size: .85rem; font-weight: 600; color: #6b7280; margin-bottom: .35rem; text-transform: uppercase; letter-spacing: .05em; }
.qb-progress-pct { font-size: 2.6rem; font-weight: 800; color: #0f2745; line-height: 1; margin-bottom: .25rem; }
.qb-progress-sub { font-size: .82rem; color: #9ca3af; margin-bottom: .75rem; }
.qb-progress-track {
    height: 10px; border-radius: 999px;
    background: #e5e7eb; overflow: hidden; max-width: 360px;
}
.qb-progress-fill {
    height: 100%; border-radius: 999px;
    background: linear-gradient(90deg, #22c55e, #16a34a);
    transition: width .5s ease;
}
.qb-progress-right {
    background: #eef2ff;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    display: flex; align-items: center; gap: 1rem;
    min-width: 230px;
}
.qb-progress-right .clip-icon {
    font-size: 2.4rem;
    color: #4f46e5;
    opacity: .85;
}
.qb-progress-right-text h6 { font-size: .92rem; font-weight: 700; color: #1e1b4b; margin: 0 0 .2rem; }
.qb-progress-right-text p  { font-size: .78rem; color: #6b7280; margin: 0; line-height: 1.4; }

/* ── Next Required Step banner ────────────────────────────────────── */
.qb-next-banner {
    background: #fff7ed;
    border: 1.5px solid #fdba74;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    display: flex; align-items: center; gap: 1rem;
    margin-bottom: 1.5rem;
}
.qb-next-banner .next-circle {
    width: 50px; height: 50px; flex-shrink: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #f97316, #ea580c);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: #fff;
}
.qb-next-banner .next-body { flex: 1; }
.qb-next-banner .next-label { font-size: .72rem; font-weight: 700; color: #f97316; text-transform: uppercase; letter-spacing: .06em; margin-bottom: .15rem; }
.qb-next-banner .next-title { font-size: 1.05rem; font-weight: 700; color: #0f2745; margin: 0 0 .2rem; }
.qb-next-banner .next-desc  { font-size: .8rem; color: #6b7280; margin: 0; }
.btn-orange {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .55rem 1.1rem;
    background: #f97316; color: #fff;
    border: none; border-radius: 8px;
    font-size: .83rem; font-weight: 600;
    cursor: pointer; white-space: nowrap; text-decoration: none;
    transition: background .15s;
    flex-shrink: 0;
}
.btn-orange:hover { background: #ea580c; color: #fff; }
.btn-orange:disabled { opacity: .55; cursor: not-allowed; }

/* ── Step cards ───────────────────────────────────────────────────── */
.qb-step-card {
    background: #fff;
    border: 1.5px solid #e9ecef;
    border-radius: 14px;
    padding: 1.25rem 1.4rem;
    margin-bottom: 1rem;
    transition: box-shadow .2s;
}
.qb-step-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.08); }
.qb-step-card.pending-card { border-color: #fdba74; background: #fff; }

.qb-step-header { display: flex; align-items: flex-start; gap: .9rem; margin-bottom: 1rem; }

.qb-step-num {
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; font-weight: 700; color: #fff;
    flex-shrink: 0; margin-top: .1rem;
}
.qb-step-num.green  { background: #22c55e; }
.qb-step-num.orange { background: #f97316; }

.qb-step-icon-box {
    width: 50px; height: 50px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.qb-step-icon-box.green  { background: #f0fdf4; color: #16a34a; }
.qb-step-icon-box.orange { background: #fff7ed; color: #f97316; }
.qb-step-icon-box.blue   { background: #eff6ff; color: #1d4ed8; }

.qb-step-meta { flex: 1; min-width: 0; }
.qb-step-meta .step-title-row { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; margin-bottom: .2rem; }
.qb-step-meta h5 { font-size: .97rem; font-weight: 700; color: #0f2745; margin: 0; }
.qb-step-meta p  { font-size: .8rem; color: #6b7280; margin: 0; line-height: 1.45; }

/* Status pills */
.s-pill {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .18rem .6rem; border-radius: 999px;
    font-size: .72rem; font-weight: 600;
}
.s-pill.green  { background: #dcfce7; color: #15803d; }
.s-pill.orange { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.s-pill.blue   { background: #dbeafe; color: #1d4ed8; }

/* Stat boxes */
.qb-stats-row  { display: flex; gap: .65rem; flex-wrap: wrap; margin-bottom: 1rem; }
.qb-stat-box {
    background: #f8fafc; border: 1px solid #e9ecef;
    border-radius: 10px; padding: .55rem .9rem; min-width: 72px; text-align: center;
}
.qb-stat-box .sv { font-size: 1.3rem; font-weight: 800; line-height: 1; color: #0f2745; }
.qb-stat-box .sv.green  { color: #16a34a; }
.qb-stat-box .sv.orange { color: #f97316; }
.qb-stat-box .sv.sm     { font-size: .8rem; font-weight: 600; color: #6b7280; }
.qb-stat-box .sl { font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; margin-top: .1rem; }

/* Info rows (connection status) */
.qb-info-box { background: #f8fafc; border: 1px solid #e9ecef; border-radius: 10px; padding: .6rem 1rem; margin-bottom: 1rem; }
.qb-info-row { display: flex; align-items: center; gap: .75rem; padding: .35rem 0; font-size: .83rem; }
.qb-info-row:not(:last-child) { border-bottom: 1px solid #f0f0f0; }
.qb-info-row .il { color: #9ca3af; width: 80px; flex-shrink: 0; font-weight: 500; }
.qb-info-row .iv { font-weight: 600; color: #0f2745; }
.qb-info-row .iv.green { color: #16a34a; }
.qb-info-row .iv.pink  { color: #db2777; font-family: monospace; font-size: .78rem; }

/* Action row */
.qb-action-row { display: flex; align-items: center; gap: .65rem; flex-wrap: wrap; }
.btn-s-primary {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .48rem 1rem; border-radius: 8px;
    font-size: .82rem; font-weight: 600;
    background: #1d4ed8; color: #fff; border: none;
    cursor: pointer; text-decoration: none;
    transition: background .15s;
}
.btn-s-primary:hover { background: #1e40af; color: #fff; }
.btn-s-primary:disabled { opacity: .55; cursor: not-allowed; }
.btn-s-outline {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .46rem 1rem; border-radius: 8px;
    font-size: .82rem; font-weight: 600;
    background: #fff; color: #374151;
    border: 1.5px solid #d1d5db; cursor: pointer;
    text-decoration: none; transition: border-color .15s, color .15s;
}
.btn-s-outline:hover { border-color: #1d4ed8; color: #1d4ed8; }
.btn-s-outline:disabled { opacity: .55; cursor: not-allowed; }

/* Services list */
.svc-cols { display: grid; grid-template-columns: 1fr 1fr; gap: .3rem .5rem; margin-bottom: .75rem; }
.svc-cols .svc-col-head { font-size: .75rem; font-weight: 700; color: #374151; margin-bottom: .35rem; }
.svc-item { display: flex; align-items: center; gap: .4rem; font-size: .8rem; color: #374151; padding: .2rem 0; }
.svc-item i { font-size: .75rem; }
.svc-item.done i { color: #16a34a; }
.svc-item.pend i { color: #f97316; }

.qb-svc-ok-strip {
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 8px; padding: .5rem .9rem;
    font-size: .8rem; color: #15803d; font-weight: 500;
    display: flex; align-items: center; gap: .5rem;
}
.qb-invoice-ok-strip {
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 8px; padding: .5rem .9rem;
    font-size: .8rem; color: #15803d; font-weight: 500;
    display: flex; align-items: center; gap: .5rem;
    margin-top: .75rem;
}

/* ── Checklist sidebar ────────────────────────────────────────────── */
.qb-checklist-card {
    background: #fff; border: 1.5px solid #e9ecef;
    border-radius: 14px; padding: 1.1rem 1.2rem;
    margin-bottom: 1rem;
}
.qb-checklist-card h6 { font-size: .88rem; font-weight: 700; color: #0f2745; margin-bottom: 1rem; }
.cl-item { display: flex; align-items: flex-start; gap: .6rem; padding: .55rem 0; border-bottom: 1px solid #f3f4f6; }
.cl-item:last-child { border-bottom: none; padding-bottom: 0; }
.cl-icon { width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; margin-top: .05rem;
    display: flex; align-items: center; justify-content: center; font-size: .65rem; }
.cl-icon.green  { background: #22c55e; color: #fff; }
.cl-icon.orange { background: #f97316; color: #fff; font-size: .7rem; }
.cl-icon.gray   { background: #e5e7eb; color: #9ca3af; }
.cl-text .cl-title  { font-size: .8rem; font-weight: 600; color: #0f2745; }
.cl-text .cl-status { font-size: .72rem; font-weight: 600; margin-top: .05rem; }
.cl-text .cl-status.green  { color: #16a34a; }
.cl-text .cl-status.orange { color: #f97316; }
.cl-text .cl-status.gray   { color: #9ca3af; }

.qb-trophy-card {
    background: #f0fdf4; border: 1.5px solid #bbf7d0;
    border-radius: 14px; padding: 1.1rem 1rem;
    text-align: center;
}
.qb-trophy-card .trophy-circle {
    width: 48px; height: 48px; border-radius: 50%;
    background: #dcfce7; display: flex; align-items: center;
    justify-content: center; font-size: 1.3rem; color: #16a34a;
    margin: 0 auto .7rem;
}
.qb-trophy-card h6 { font-size: .88rem; font-weight: 700; color: #15803d; margin-bottom: .3rem; }
.qb-trophy-card p  { font-size: .77rem; color: #6b7280; margin: 0; line-height: 1.4; }

/* ── Need Help footer ─────────────────────────────────────────────── */
.qb-help-card {
    background: #eff6ff; border: 1.5px solid #bfdbfe;
    border-radius: 14px; padding: 1.1rem 1.4rem;
    display: flex; align-items: center; gap: 1rem;
    margin-top: 1.25rem;
}
.qb-help-card .help-icon {
    width: 44px; height: 44px; border-radius: 50%;
    background: #3b82f6; color: #fff; font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.qb-help-card .help-text h6 { font-size: .9rem; font-weight: 700; color: #1e3a5f; margin: 0 0 .15rem; }
.qb-help-card .help-text p  { font-size: .78rem; color: #6b7280; margin: 0; }

/* ── Other panels ─────────────────────────────────────────────────── */
.other-panel-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    padding: 1.75rem;
}
.other-panel-card h5 { font-weight: 700; color: #0f2745; }

/* ── Responsive ───────────────────────────────────────────────────── */
@media (max-width: 991px) {
    .qb-cols { flex-direction: column; }
    .qb-checklist-col { width: 100%; }
}
@media (max-width: 768px) {
    .settings-layout { flex-direction: column; }
    .settings-sidebar { width: 100%; position: static; }
    .s-sidebar-card nav { display: flex; flex-wrap: wrap; gap: .25rem; }
    .s-sidebar-card .nav-link { width: auto; }
    .qb-progress-right { min-width: 0; }
    .qb-progress-row { flex-direction: column; align-items: flex-start; }
    .qb-main-card { padding: 1.25rem; }
    .svc-cols { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
@php
    $user   = auth()->user();
    $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';

    $activeTab   = session('active_tab', 'quickbooks');
    $panelMap    = ['map-pane' => 'maps', 'dwolla-alert-pane' => 'dwolla'];
    $activePanel = $panelMap[$activeTab] ?? $activeTab;
    if (!in_array($activePanel, ['quickbooks', 'dwolla', 'maps'])) {
        $activePanel = 'quickbooks';
    }

    // Progress — 6 steps: Connection, Accounts, Terms, Payment Methods, Expense Categories, Services
    $stepsDone = collect([
        $quickbooksConnected,
        $accountsSynced,
        $termsSynced,
        $paymentMethodsSynced,
        $expenseCategoriesSynced,
        $mandatoryAllSynced,
    ]);
    $totalSteps     = $stepsDone->count();
    $completedSteps = $stepsDone->filter()->count();
    $progressPct    = (int) round($completedSteps / $totalSteps * 100);
    $allDone        = $completedSteps === $totalSteps;

    // First incomplete step label for the "Next Required Step" banner
    $nextStep = null;
    if (!$quickbooksConnected)       $nextStep = ['label' => 'Connect to QuickBooks',        'desc' => 'Connect your QuickBooks account to enable syncing and invoice generation.',                                                 'route' => route($prefix.'.quickbooks.auth'),                         'method' => 'get',  'btn' => 'Connect QuickBooks',        'icon' => 'fa-plug'];
    elseif (!$accountsSynced)        $nextStep = ['label' => 'Sync Chart of Accounts',       'desc' => 'Chart of Accounts is required for proper accounting and invoice generation in QuickBooks.',                               'route' => route($prefix.'.quickbooks.sync-accounts'),               'method' => 'post', 'btn' => 'Sync Chart of Accounts',    'icon' => 'fa-sync-alt'];
    elseif (!$termsSynced)           $nextStep = ['label' => 'Sync Payment Terms',           'desc' => 'Payment Terms must be synced so QuickBooks Term IDs stay accurate for this company.',                                     'route' => route($prefix.'.quickbooks.sync-terms'),                  'method' => 'post', 'btn' => 'Sync Terms',                 'icon' => 'fa-sync-alt'];
    elseif (!$paymentMethodsSynced)  $nextStep = ['label' => 'Sync Payment Methods',         'desc' => 'Payment Methods are required for creating Ambassador Payout and Reimbursement expenses.',                                  'route' => route($prefix.'.quickbooks.sync-payment-methods'),        'method' => 'post', 'btn' => 'Sync Payment Methods',      'icon' => 'fa-sync-alt'];
    elseif (!$expenseCategoriesSynced) $nextStep = ['label' => 'Sync Expense Categories',   'desc' => 'Expense Categories are required for Ambassador Payout and Reimbursement expenses.',                                       'route' => route($prefix.'.quickbooks.sync-expense-categories'),     'method' => 'post', 'btn' => 'Sync Expense Categories',   'icon' => 'fa-sync-alt'];
    elseif (!$mandatoryAllSynced)    $nextStep = ['label' => 'Sync Services',                'desc' => 'All 7 mandatory system services must be synced to QuickBooks before creating invoices.',                                   'route' => route($prefix.'.services.sync-to-quickbooks'),            'method' => 'post', 'btn' => 'Sync Services',               'icon' => 'fa-sync-alt'];
@endphp

<div class="container-fluid px-4 py-4" style="max-width:1200px;">

    {{-- Page heading --}}
    <div class="mb-4">
        <h4 class="fw-bold mb-0" style="color:#0f2745;">Settings</h4>
        <p class="text-muted small mb-0">Manage application configuration</p>
    </div>

    {{-- Flash alerts --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="settings-layout">

        {{-- ── Sidebar ─────────────────────────────────────────────── --}}
        <div class="settings-sidebar">
            <div class="s-sidebar-card">
                <nav id="settingsNav">
                    <button class="nav-link {{ $activePanel === 'quickbooks' ? 'active' : '' }}"
                            data-panel="quickbooks" type="button">
                        <i class="fas fa-plug"></i> QuickBooks
                    </button>
                    <button class="nav-link {{ $activePanel === 'dwolla' ? 'active' : '' }}"
                            data-panel="dwolla" type="button">
                        <i class="fas fa-bell"></i> Dwolla Balance Alert
                    </button>
                    <button class="nav-link {{ $activePanel === 'maps' ? 'active' : '' }}"
                            data-panel="maps" type="button">
                        <i class="fas fa-map-marker-alt"></i> Google Maps
                    </button>
                </nav>
            </div>
        </div>

        {{-- ── Content ──────────────────────────────────────────────── --}}
        <div class="settings-content">

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- QuickBooks Panel                                      --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="settings-panel {{ $activePanel === 'quickbooks' ? 'active' : '' }}" id="panel-quickbooks">

                {{-- ── Progress header (outside the two-column area) ── --}}
                <div class="qb-main-card mb-0" style="border-radius:18px 18px 0 0; padding-bottom:1.5rem;">
                    <div class="qb-progress-row">
                        <div class="qb-progress-left">
                            <div class="qb-progress-heading">QuickBooks Setup Progress</div>
                            <div class="qb-progress-pct">{{ $progressPct }}%</div>
                            <div class="qb-progress-sub">{{ $completedSteps }} of {{ $totalSteps }} steps completed</div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $progressPct }}%"></div>
                              </div>
                        </div>
                        <div class="qb-progress-right">
                            <i class="fas {{ $allDone ? 'fa-check-circle' : 'fa-clipboard-list' }} clip-icon"></i>
                            <div class="qb-progress-right-text">
                                @if ($allDone)
                                    <h6>All Set!</h6>
                                    <p>QuickBooks is fully configured. You can now create and publish invoices.</p>
                                @else
                                    <h6>Almost there!</h6>
                                    <p>Complete the remaining setup before creating invoices.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Next Required Step banner --}}
                    @if ($nextStep)
                        <div class="qb-next-banner">
                            <div class="next-circle">
                                <i class="fas fa-exclamation"></i>
                            </div>
                            <div class="next-body">
                                <div class="next-label">Next Required Step</div>
                                <div class="next-title">{{ $nextStep['label'] }}</div>
                                <p class="next-desc">{{ $nextStep['desc'] }}</p>
                            </div>
                            @if ($nextStep['method'] === 'post')
                                <form action="{{ $nextStep['route'] }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn-ds btn-orange-ds btn-sm">
                                        <i class="fas {{ $nextStep['icon'] }}"></i> {{ $nextStep['btn'] }}
                                    </button>
                                </form>
                            @else
                                <a href="{{ $nextStep['route'] }}" class="btn-ds btn-orange-ds btn-sm">
                                    <i class="fas {{ $nextStep['icon'] }}"></i> {{ $nextStep['btn'] }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- ── Two-column body ──────────────────────────────── --}}
                <div class="qb-main-card" style="border-radius:0 0 18px 18px; padding-top:1.5rem; border-top:1px solid #f0f0f0;">
                    <div class="qb-cols">

                        {{-- LEFT: Step cards --}}
                        <div class="qb-steps">

                            {{-- Step 1 — Connection --}}
                            <div class="qb-step-card {{ !$quickbooksConnected ? 'pending-card' : '' }}">
                                <div class="qb-step-header">
                                    <div class="qb-step-num {{ $quickbooksConnected ? 'green' : 'orange' }}">1</div>
                                    <div class="qb-step-icon-box {{ $quickbooksConnected ? 'green' : 'orange' }}">
                                        <i class="fas fa-link"></i>
                                    </div>
                                    <div class="qb-step-meta">
                                        <div class="step-title-row">
                                            <h5>QuickBooks Connection</h5>
                                            @if ($quickbooksConnected)
                                                <span class="s-pill green"><i class="fas fa-check-circle"></i> Connected</span>
                                            @else
                                                <span class="s-pill orange"><i class="fas fa-exclamation-circle"></i> Not Connected</span>
                                            @endif
                                        </div>
                                        <p>{{ $quickbooksConnected ? 'Your QuickBooks account is connected and active.' : 'Connect your QuickBooks account to enable sync and invoice generation.' }}</p>
                                    </div>
                                </div>

                                @if ($quickbooksConnected)
                                    <div class="qb-info-box">
                                        <div class="qb-info-row">
                                            <span class="il">Status</span>
                                            <span class="iv green"><i class="fas fa-check-circle me-1"></i>Connected</span>
                                        </div>
                                        <div class="qb-info-row">
                                            <span class="il">Realm ID</span>
                                            <span class="iv pink">{{ $qbRealmId }}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="qb-action-row">
                                    @if ($quickbooksConnected)
                                        <a href="{{ route($prefix.'.quickbooks.auth') }}" class="btn-s-outline">
                                            <i class="fas fa-sync-alt"></i> Reconnect
                                        </a>
                                    @else
                                        <a href="{{ route($prefix.'.quickbooks.auth') }}" class="btn-s-primary">
                                            <i class="fas fa-link"></i> Connect to QuickBooks
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Step 2 — Chart of Accounts --}}
                            <div class="qb-step-card {{ !$accountsSynced ? 'pending-card' : '' }}">
                                <div class="qb-step-header">
                                    <div class="qb-step-num {{ $accountsSynced ? 'green' : 'orange' }}">2</div>
                                    <div class="qb-step-icon-box {{ $accountsSynced ? 'green' : 'orange' }}">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <div class="qb-step-meta">
                                        <div class="step-title-row">
                                            <h5>Chart of Accounts</h5>
                                            @if ($accountsSynced)
                                                <span class="s-pill green"><i class="fas fa-check-circle"></i> Synced</span>
                                            @else
                                                <span class="s-pill orange">Not Synced</span>
                                            @endif
                                        </div>
                                        <p>Required for Services, Expenses, Invoice generation and Payments.</p>
                                    </div>
                                </div>

                                <div class="qb-stats-row">
                                    <div class="qb-stat-box">
                                        <div class="sv {{ $totalAccounts > 0 ? 'green' : '' }}">{{ $totalAccounts }}</div>
                                        <div class="sl">Accounts</div>
                                    </div>
                                    <div class="qb-stat-box">
                                        <div class="sv sm">{{ $lastAccountSync ? \Carbon\Carbon::parse($lastAccountSync)->diffForHumans() : '—' }}</div>
                                        <div class="sl">Last Sync</div>
                                    </div>
                                </div>

                                <div class="qb-action-row">
                                    <form action="{{ route($prefix.'.quickbooks.sync-accounts') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="{{ $accountsSynced ? 'btn-s-outline' : 'btn-ds btn-orange-ds btn-sm' }}" {{ !$quickbooksConnected ? 'disabled' : '' }}>
                                            <i class="fas fa-sync-alt"></i> Sync Chart of Accounts
                                        </button>
                                    </form>
                                    @if (!$quickbooksConnected)
                                        <span class="text-muted" style="font-size:.78rem;"><i class="fas fa-lock me-1"></i>Connect first</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Step 3 — Payment Terms --}}
                            <div class="qb-step-card {{ !$termsSynced ? 'pending-card' : '' }}">
                                <div class="qb-step-header">
                                    <div class="qb-step-num {{ $termsSynced ? 'green' : 'orange' }}">3</div>
                                    <div class="qb-step-icon-box {{ $termsSynced ? 'green' : 'orange' }}">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                    <div class="qb-step-meta">
                                        <div class="step-title-row">
                                            <h5>Payment Terms</h5>
                                            @if ($termsSynced)
                                                <span class="s-pill green"><i class="fas fa-check-circle"></i> Synced</span>
                                            @else
                                                <span class="s-pill orange">Not Synced</span>
                                            @endif
                                        </div>
                                        <p>Payment Terms keep QuickBooks Term IDs up to date after reconnecting or resetting your company.</p>
                                    </div>
                                </div>

                                <div class="qb-stats-row">
                                    <div class="qb-stat-box">
                                        <div class="sv {{ $totalTerms > 0 ? 'green' : '' }}">{{ $totalTerms }}</div>
                                        <div class="sl">Terms</div>
                                    </div>
                                    <div class="qb-stat-box">
                                        <div class="sv sm">{{ $lastTermSync ? \Carbon\Carbon::parse($lastTermSync)->diffForHumans() : '—' }}</div>
                                        <div class="sl">Last Sync</div>
                                    </div>
                                </div>

                                <div class="qb-action-row">
                                    <form action="{{ route($prefix.'.quickbooks.sync-terms') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="{{ $termsSynced ? 'btn-s-outline' : 'btn-ds btn-orange-ds btn-sm' }}" {{ !$quickbooksConnected ? 'disabled' : '' }}>
                                            <i class="fas fa-sync-alt"></i> Sync Terms
                                        </button>
                                    </form>
                                    @if (!$quickbooksConnected)
                                        <span class="text-muted" style="font-size:.78rem;"><i class="fas fa-lock me-1"></i>Connect first</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Step 4 — Payment Methods --}}
                            <div class="qb-step-card {{ !$paymentMethodsSynced ? 'pending-card' : '' }}">
                                <div class="qb-step-header">
                                    <div class="qb-step-num {{ $paymentMethodsSynced ? 'green' : 'orange' }}">4</div>
                                    <div class="qb-step-icon-box {{ $paymentMethodsSynced ? 'green' : 'orange' }}">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="qb-step-meta">
                                        <div class="step-title-row">
                                            <h5>Payment Methods</h5>
                                            @if ($paymentMethodsSynced)
                                                <span class="s-pill green"><i class="fas fa-check-circle"></i> Synced</span>
                                            @else
                                                <span class="s-pill orange">Not Synced</span>
                                            @endif
                                        </div>
                                        <p>Sync available QuickBooks Payment Methods (Bank &amp; Credit Card accounts) required for Ambassador Payout and Reimbursement expense creation.</p>
                                    </div>
                                </div>

                                <div class="qb-stats-row">
                                    <div class="qb-stat-box">
                                        <div class="sv {{ $totalPaymentMethods > 0 ? 'green' : '' }}">{{ $totalPaymentMethods }}</div>
                                        <div class="sl">Methods</div>
                                    </div>
                                    <div class="qb-stat-box">
                                        <div class="sv sm">{{ $lastPaymentMethodSync ? \Carbon\Carbon::parse($lastPaymentMethodSync)->diffForHumans() : '\u2014' }}</div>
                                        <div class="sl">Last Sync</div>
                                    </div>
                                </div>

                                <div class="qb-action-row">
                                    <form action="{{ route($prefix.'.quickbooks.sync-payment-methods') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="{{ $paymentMethodsSynced ? 'btn-s-outline' : 'btn-ds btn-orange-ds btn-sm' }}" {{ !$quickbooksConnected ? 'disabled' : '' }}>
                                            <i class="fas fa-sync-alt"></i> Sync Payment Methods
                                        </button>
                                    </form>
                                    <a href="{{ route($prefix.'.payment-methods.index') }}" class="btn-s-outline">
                                        <i class="fas fa-list"></i> Manage Methods
                                    </a>
                                    @if (!$quickbooksConnected)
                                        <span class="text-muted" style="font-size:.78rem;"><i class="fas fa-lock me-1"></i>Connect first</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Step 5 — Expense Categories --}}
                            <div class="qb-step-card {{ !$expenseCategoriesSynced ? 'pending-card' : '' }}">
                                <div class="qb-step-header">
                                    <div class="qb-step-num {{ $expenseCategoriesSynced ? 'green' : 'orange' }}">5</div>
                                    <div class="qb-step-icon-box {{ $expenseCategoriesSynced ? 'green' : 'orange' }}">
                                        <i class="fas fa-tags"></i>
                                    </div>
                                    <div class="qb-step-meta">
                                        <div class="step-title-row">
                                            <h5>Expense Categories</h5>
                                            @if ($expenseCategoriesSynced)
                                                <span class="s-pill green"><i class="fas fa-check-circle"></i> Synced</span>
                                            @else
                                                <span class="s-pill orange">Not Synced</span>
                                            @endif
                                        </div>
                                        <p>Sync Expense Categories used for Ambassador Payouts and Reimbursements. The default <strong>Ambassador Payouts</strong> category is created automatically.</p>
                                    </div>
                                </div>

                                <div class="qb-stats-row">
                                    <div class="qb-stat-box">
                                        <div class="sv">{{ $totalExpenseCategories }}</div>
                                        <div class="sl">Total</div>
                                    </div>
                                    <div class="qb-stat-box">
                                        <div class="sv green">{{ $syncedExpenseCategories }}</div>
                                        <div class="sl">Synced</div>
                                    </div>
                                    <div class="qb-stat-box">
                                        <div class="sv {{ $pendingExpenseCategories > 0 ? 'orange' : 'green' }}">{{ $pendingExpenseCategories }}</div>
                                        <div class="sl">Pending</div>
                                    </div>
                                    <div class="qb-stat-box">
                                        <div class="sv sm">{{ $lastExpenseCategorySync ? \Carbon\Carbon::parse($lastExpenseCategorySync)->diffForHumans() : '\u2014' }}</div>
                                        <div class="sl">Last Sync</div>
                                    </div>
                                </div>

                                {{-- Category list --}}
                                @if ($allExpenseCategories->count() > 0)
                                    <div class="svc-cols mb-2">
                                        @foreach ($allExpenseCategories->chunk(ceil($allExpenseCategories->count() / 2)) as $chunk)
                                            <div>
                                                @foreach ($chunk as $cat)
                                                    <div class="svc-item {{ $cat->quickbooks_account_id ? 'done' : 'pend' }}">
                                                        <i class="fas {{ $cat->quickbooks_account_id ? 'fa-check-circle' : 'fa-clock' }}"></i>
                                                        {{ $cat->name }}
                                                        @if ($cat->is_default)
                                                            <span style="font-size:.68rem;color:#9ca3af;">(default)</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="qb-action-row">
                                    <form action="{{ route($prefix.'.quickbooks.sync-expense-categories') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="{{ $expenseCategoriesSynced ? 'btn-s-outline' : 'btn-ds btn-orange-ds btn-sm' }}" {{ !$quickbooksConnected ? 'disabled' : '' }}>
                                            <i class="fas fa-sync-alt"></i> Sync Expense Categories
                                        </button>
                                    </form>
                                    <a href="{{ route($prefix.'.expense-categories.index') }}" class="btn-s-outline">
                                        <i class="fas fa-list"></i> Manage Categories
                                    </a>
                                    @if (!$quickbooksConnected)
                                        <span class="text-muted" style="font-size:.78rem;"><i class="fas fa-lock me-1"></i>Connect first</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Step 6 — Services --}}
                            <div class="qb-step-card {{ !$mandatoryAllSynced ? 'pending-card' : '' }}">
                                <div class="qb-step-header">
                                    <div class="qb-step-num {{ $mandatoryAllSynced ? 'green' : 'orange' }}">6</div>
                                    <div class="qb-step-icon-box {{ $mandatoryAllSynced ? 'green' : 'orange' }}">
                                        <i class="fas fa-concierge-bell"></i>
                                    </div>
                                    <div class="qb-step-meta">
                                        <div class="step-title-row">
                                            <h5>Services</h5>
                                            @if ($mandatoryAllSynced)
                                                <span class="s-pill green"><i class="fas fa-check-circle"></i> All Synced</span>
                                            @else
                                                <span class="s-pill orange">Pending</span>
                                            @endif
                                        </div>
                                        <p>{{ $mandatoryAllSynced ? 'Required invoice services are synced and ready.' : 'All 7 mandatory system services must be synced before creating invoices.' }}</p>
                                    </div>
                                </div>

                                <div class="qb-stats-row">
                                    <div class="qb-stat-box">
                                        <div class="sv">{{ $totalServices }}</div>
                                        <div class="sl">Total</div>
                                    </div>
                                    <div class="qb-stat-box">
                                        <div class="sv green">{{ $syncedServices }}</div>
                                        <div class="sl">Synced</div>
                                    </div>
                                    <div class="qb-stat-box">
                                        <div class="sv {{ $pendingServices > 0 ? 'orange' : 'green' }}">{{ $pendingServices }}</div>
                                        <div class="sl">Pending</div>
                                    </div>
                                </div>

                                {{-- Services two-column list --}}
                                <div class="svc-cols mb-2">
                                    <div>
                                        <div class="svc-col-head">Mandatory System Services</div>
                                        @foreach ($mandatoryServices as $svc)
                                            <div class="svc-item {{ $svc->qb_item_id ? 'done' : 'pend' }}">
                                                <i class="fas {{ $svc->qb_item_id ? 'fa-check-circle' : 'fa-clock' }}"></i>
                                                {{ $svc->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                    @if ($customServices->count() > 0)
                                        <div>
                                            <div class="svc-col-head">Custom Services</div>
                                            @foreach ($customServices as $svc)
                                                <div class="svc-item {{ $svc->qb_item_id ? 'done' : 'pend' }}" style="{{ !$svc->qb_item_id ? 'opacity:.6' : '' }}">
                                                    <i class="fas {{ $svc->qb_item_id ? 'fa-check-circle' : 'fa-circle' }}"></i>
                                                    {{ $svc->name }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                @if ($mandatoryAllSynced)
                                    <div class="qb-svc-ok-strip mb-3">
                                        <i class="fas fa-check-circle"></i> All system services are synced and ready.
                                    </div>
                                @endif

                                <div class="qb-action-row">
                                    <form action="{{ route($prefix.'.services.sync-to-quickbooks') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="{{ $mandatoryAllSynced ? 'btn-s-outline' : 'btn-ds btn-orange-ds btn-sm' }}" {{ !$quickbooksConnected ? 'disabled' : '' }}>
                                            <i class="fas fa-sync-alt"></i> Sync Services to QuickBooks
                                        </button>
                                    </form>
                                    <a href="{{ route($prefix.'.services.index') }}" class="btn-s-outline">
                                        <i class="fas fa-list"></i> Manage Services
                                    </a>
                                </div>
                            </div>

                            {{-- Step 7 — Ready for Invoice & Expense Processing --}}
                            <div class="qb-step-card">
                                <div class="qb-step-header" style="margin-bottom:{{ $qbSetupReady ? '.75rem' : '0' }};">
                                    <div class="qb-step-num {{ $qbSetupReady ? 'green' : 'orange' }}">7</div>
                                    <div class="qb-step-icon-box {{ $qbSetupReady ? 'green' : 'orange' }}">
                                        <i class="fas fa-envelope-open-text"></i>
                                    </div>
                                    <div class="qb-step-meta">
                                        <div class="step-title-row">
                                            <h5>Ready for Invoice &amp; Expense Processing</h5>
                                            @if ($qbSetupReady)
                                                <span class="s-pill green"><i class="fas fa-check-circle"></i> Completed</span>
                                            @else
                                                <span class="s-pill orange">Pending</span>
                                            @endif
                                        </div>
                                        <p>{{ $qbSetupReady ? 'You are all set to create and publish invoices and process expenses in QuickBooks.' : 'Complete the steps above to unlock invoice creation and expense processing.' }}</p>
                                    </div>
                                </div>
                                @if ($qbSetupReady)
                                    <div class="qb-invoice-ok-strip">
                                        <i class="fas fa-check-circle"></i> You can now create invoices, publish to QuickBooks, and process Ambassador Payout and Reimbursement expenses.
                                    </div>
                                @endif
                            </div>

                        </div>{{-- /qb-steps --}}

                        {{-- RIGHT: Checklist + trophy --}}
                        <div class="qb-checklist-col">

                            <div class="qb-checklist-card">
                                <h6><i class="fas fa-tasks me-1"></i> Setup Checklist</h6>
                                @php
                                    $clItems = [
                                        ['label' => 'QuickBooks Connected',        'done' => $quickbooksConnected],
                                        ['label' => 'Chart of Accounts',           'done' => $accountsSynced],
                                        ['label' => 'Payment Terms',               'done' => $termsSynced],
                                        ['label' => 'Payment Methods',             'done' => $paymentMethodsSynced],
                                        ['label' => 'Expense Categories',          'done' => $expenseCategoriesSynced],
                                        ['label' => 'Services',                    'done' => $mandatoryAllSynced],
                                        ['label' => 'Ready for Invoice & Expense', 'done' => $qbSetupReady],
                                        ['label' => 'All Set!',                    'done' => $allDone],
                                    ];
                                @endphp
                                @foreach ($clItems as $cl)
                                    <div class="cl-item">
                                        <div class="cl-icon {{ $cl['done'] ? 'green' : 'orange' }}">
                                            <i class="fas {{ $cl['done'] ? 'fa-check' : 'fa-exclamation' }}"></i>
                                        </div>
                                        <div class="cl-text">
                                            <div class="cl-title">{{ $cl['label'] }}</div>
                                            <div class="cl-status {{ $cl['done'] ? 'green' : 'orange' }}">
                                                {{ $cl['done'] ? 'Completed' : 'Pending' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="qb-trophy-card">
                                <div class="trophy-circle">
                                    <i class="fas {{ $allDone ? 'fa-trophy' : 'fa-hourglass-half' }}"></i>
                                </div>
                                @if ($allDone)
                                    <h6>You're all set!</h6>
                                    <p>QuickBooks is fully configured. Start creating invoices.</p>
                                @else
                                    <h6>You're almost ready!</h6>
                                    <p>Complete the pending steps to start creating invoices.</p>
                                @endif
                            </div>

                        </div>{{-- /qb-checklist-col --}}

                    </div>{{-- /qb-cols --}}

                    {{-- Need Help footer --}}
                    {{-- <div class="qb-help-card">
                        <div class="help-icon"><i class="fas fa-lightbulb"></i></div>
                        <div class="help-text flex-grow-1">
                            <h6>Need Help?</h6>
                            <p>Learn how QuickBooks integration works or contact support.</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="https://developer.intuit.com/app/developer/qbo/docs/develop" target="_blank" class="btn-s-outline">
                                <i class="fas fa-book"></i> View Integration Guide
                            </a>
                            <a href="mailto:support@bluestoneapps.com" class="btn-s-outline">
                                <i class="fas fa-headset"></i> Contact Support
                            </a>
                        </div>
                    </div> --}}

                </div>{{-- /qb-main-card body --}}
            </div>{{-- /panel-quickbooks --}}

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- Dwolla Panel                                          --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="settings-panel {{ $activePanel === 'dwolla' ? 'active' : '' }}" id="panel-dwolla">
                <div class="other-panel-card">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="width:40px;height:40px;border-radius:10px;background:#fffbeb;display:flex;align-items:center;justify-content:center;color:#d97706;font-size:1.1rem;">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Dwolla Balance Alert</h5>
                            <p class="text-muted small mb-0">Set a minimum Dwolla balance threshold for alerts.</p>
                        </div>
                    </div>
                    <hr style="border-color:#f0f0f0;">
                    <form action="{{ route($prefix.'.settings.updateSetting') }}" method="POST">
                        @csrf
                        <input type="hidden" name="active_tab" value="dwolla-alert-pane">
                        <input type="hidden" name="setting" value="dwolla_balance_alert">
                        <div class="mb-3" style="max-width:360px;">
                            <label for="fixedFloorAmount" class="form-label fw-semibold">Bottom Line Limit (USD)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="fixedFloorAmount"
                                name="fixed_floor_amount"
                                value="{{ $dwollaBalanceAlertSetting->fixed_floor_amount ?? '' }}"
                                placeholder="e.g. 50000">
                            <div class="form-text">When set, required minimum is max(bottom line limit, 14-day forecast + buffer).</div>
                        </div>
                        <button type="submit" class="btn-s-primary">Save Changes</button>
                    </form>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- Google Maps Panel                                     --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="settings-panel {{ $activePanel === 'maps' ? 'active' : '' }}" id="panel-maps">
                <div class="other-panel-card">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="width:40px;height:40px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#1d4ed8;font-size:1.1rem;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Google Maps</h5>
                            <p class="text-muted small mb-0">Configure Google Maps API keys for map display, Places, geocoding, and timezone lookup.</p>
                        </div>
                    </div>
                    <hr style="border-color:#f0f0f0;">
                    <form action="{{ route($prefix.'.settings.updateSetting') }}" method="POST">
                        @csrf
                        <input type="hidden" name="active_tab" value="map-pane">
                        <input type="hidden" name="setting" value="map">
                        <div class="mb-3" style="max-width:480px;">
                            <label for="apiKey" class="form-label fw-semibold">API Key</label>
                            <input type="text" class="form-control" id="apiKey" name="public_key"
                                value="{{ $mapSetting->public_key ?? '' }}" placeholder="Enter your Google Maps API Key">
                        </div>
                        <div class="mb-3" style="max-width:480px;">
                            <label for="serverApiKey" class="form-label fw-semibold">Server API Key</label>
                            <input type="text" class="form-control" id="serverApiKey" name="server_key"
                                value="{{ $mapSetting->server_key ?? '' }}" placeholder="Optional key for geocoding and timezone lookup">
                            <small class="text-muted">Leave blank to use the API key above. If no saved key exists, the app falls back to GOOGLE_MAPS_API_KEY.</small>
                        </div>
                        <div class="mb-3" style="max-width:200px;">
                            <label for="mapZoom" class="form-label fw-semibold">Default Zoom Level</label>
                            <input type="number" class="form-control" id="mapZoom" name="zoom"
                                value="{{ $mapSetting->zoom ?? '' }}" min="1" max="20" placeholder="e.g. 12">
                        </div>
                        <button type="submit" class="btn-s-primary">Save Changes</button>
                    </form>
                </div>
            </div>

        </div>{{-- /settings-content --}}
    </div>{{-- /settings-layout --}}
</div>

<script>
(function () {
    const nav    = document.getElementById('settingsNav');
    const panels = document.querySelectorAll('.settings-panel');
    nav.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-panel]');
        if (!btn) return;
        nav.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        panels.forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const target = document.getElementById('panel-' + btn.dataset.panel);
        if (target) target.classList.add('active');
    });
})();
</script>
@endsection
