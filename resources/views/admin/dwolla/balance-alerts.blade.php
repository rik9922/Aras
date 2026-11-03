@extends('layout.app', ['page_name' => 'Dwolla Balance Alerts'])

@section('content')
@php
    $routePrefix = $isManager ? 'manager' : 'admin';
    $statusClass = 'secondary';
    if ($latest && $latest->is_breach) {
        $statusClass = $latest->severity === 'critical' ? 'danger' : 'warning';
    } elseif ($latest) {
        $statusClass = 'success';
    }
@endphp

<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Dwolla Operating Balance Alerts</h5>
        <a class="btn-ds btn-primary-outline btn-sm" href="{{ route($routePrefix.'.dwolla.balance-alerts') }}">
            Refresh
        </a>
    </div>
    <div class="fixed-border"></div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="page-card-design mb-3">
        <h6 class="mb-3">Current Status</h6>
        <div class="mb-3">
            <span class="small text-muted">Active Bottom Line Limit:</span>
            <strong>
                @if (!is_null($activeBottomLineLimit))
                    ${{ number_format((float) $activeBottomLineLimit, 2) }}
                @else
                    Not set (using forecast + buffer)
                @endif
            </strong>
        </div>
        @if ($latest)
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-{{ $statusClass }}">
                    {{ $latest->is_breach ? strtoupper($latest->severity ?? 'warning') : 'HEALTHY' }}
                </span>
                <span class="text-muted small">Last run: {{ optional($latest->created_at)->format('M d, Y H:i') }}</span>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="small text-muted">Operating Balance</div>
                    <div><strong>${{ number_format((float) $latest->operating_balance, 2) }}</strong></div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Required Minimum</div>
                    <div><strong>${{ number_format((float) $latest->required_minimum, 2) }}</strong></div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">14-Day Forecast</div>
                    <div><strong>${{ number_format((float) $latest->forecast_total, 2) }}</strong></div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Shortfall</div>
                    <div><strong>${{ number_format((float) $latest->shortfall, 2) }}</strong></div>
                </div>
            </div>
        @else
            <p class="mb-0 text-muted">No Dwolla balance alerts have been recorded yet.</p>
        @endif
    </div>

    <div class="page-card-design mb-3">
        <form method="GET" class="row g-2">
            <div class="col-auto">
                <select name="breach" class="form-select">
                    <option value="">All states</option>
                    <option value="1" {{ request('breach') === '1' ? 'selected' : '' }}>Breach</option>
                    <option value="0" {{ request('breach') === '0' ? 'selected' : '' }}>Healthy</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="severity" class="form-select">
                    <option value="">All severities</option>
                    <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn-ds btn-primary-ds" type="submit">Filter</button>
            </div>
        </form>
    </div>

    <div class="page-card-design">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Run At</th>
                        <th>Status</th>
                        <th>Operating</th>
                        <th>Required</th>
                        <th>Forecast</th>
                        <th>Shortfall</th>
                        <th>Window</th>
                        <th>Notified</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($snapshots as $row)
                        <tr>
                            <td>{{ optional($row->created_at)->format('M d, Y H:i') }}</td>
                            <td>
                                @if ($row->is_breach)
                                    <span class="badge bg-{{ $row->severity === 'critical' ? 'danger' : 'warning' }}">
                                        {{ strtoupper($row->severity ?? 'warning') }}
                                    </span>
                                @else
                                    <span class="badge bg-success">HEALTHY</span>
                                @endif
                            </td>
                            <td>${{ number_format((float) $row->operating_balance, 2) }}</td>
                            <td>${{ number_format((float) $row->required_minimum, 2) }}</td>
                            <td>${{ number_format((float) $row->forecast_total, 2) }}</td>
                            <td>${{ number_format((float) $row->shortfall, 2) }}</td>
                            <td>{{ optional($row->window_start)->format('m/d H:i') }} - {{ optional($row->window_end)->format('m/d H:i') }}</td>
                            <td>{{ $row->notified_at ? $row->notified_at->format('M d, Y H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">No snapshot history found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $snapshots->links() }}
    </div>
</div>
@endsection
