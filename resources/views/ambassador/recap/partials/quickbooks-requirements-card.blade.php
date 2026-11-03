@php
    $requirements = $quickbooksRequirements ?? [
        'show' => false,
        'ready' => true,
        'w9_completed' => true,
        'quickbooks_1099_completed' => true,
    ];

    $statusBadge = function (bool $completed) {
        return $completed
            ? '<span class="badge bg-success">Completed</span>'
            : '<span class="badge bg-warning text-dark">Pending</span>';
    };
@endphp

@if(($requirements['show'] ?? false) && ! ($requirements['ready'] ?? false))
    <div class="alert {{ ($requirements['ready'] ?? false) ? 'alert-success' : 'alert-warning' }} mb-3" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="fas fa-info-circle mt-1"></i>
            <div class="w-100">
                <div class="fw-semibold mb-1">QuickBooks requirements before submitting</div>
                <div class="small mb-2">
                    You must complete your W-9 form, and Admin must complete the required 1099 setup in QuickBooks.
                    Recap submission stays disabled until both are completed.
                </div>
                @if(! ($requirements['w9_completed'] ?? false))
                    <div class="small mb-2">
                        <a href="{{ route('ambassador.w9.show') }}" class="fw-semibold">Complete your W-9 form</a>
                    </div>
                @endif
                <div class="d-flex flex-column gap-1 small">
                    <div class="d-flex justify-content-between gap-3">
                        <span>W-9 form completed by BA</span>
                        {!! $statusBadge((bool) ($requirements['w9_completed'] ?? false)) !!}
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span>1099 setup completed by Admin in QuickBooks</span>
                        {!! $statusBadge((bool) ($requirements['quickbooks_1099_completed'] ?? false)) !!}
                    </div>
                </div>
                @error('quickbooks_requirements')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
@endif
