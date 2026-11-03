@php
    $status = $ambassador->getBankSetupStatus();
    $label = $ambassador->getBankSetupStatusLabel();
    $badgeClass = match ($status) {
        'verified' => 'success',
        'pending_verification' => 'warning',
        default => 'danger',
    };
@endphp
<span class="span-text {{ $badgeClass }}">{{ $label }}</span>
