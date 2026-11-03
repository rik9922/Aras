@php
    $row = $row ?? [];
    $assignId = $row['assign_id'] ?? null;
    $ambassadorId = (int) ($row['ambassador_id'] ?? 0);
    $assigned = ! empty($row['assigned']);
    $overbooked = ! empty($row['overbooked']);
    $hasPinata = ! empty($row['has_pinata']);
    $isNoShow = ! empty($row['is_no_show']);
    $profileImage = $row['profile_image'] ?? userProfileImageUrl(null);
@endphp
<tr class="ambassador-staffing-row"
    data-assign-id="{{ $assignId }}"
    data-ambassador-id="{{ $ambassadorId }}"
    data-override-pay="{{ $row['override_pay'] ?? 0 }}"
    data-additional-pay-reason="{{ $row['additional_pay_reason'] ?? '' }}"
    data-additional-pay-note="{{ $row['additional_pay_note'] ?? '' }}"
    data-assigned="{{ $assigned ? '1' : '0' }}"
    data-overbooked="{{ $overbooked ? '1' : '0' }}"
    data-wage="{{ $row['wage'] ?? '0.00' }}"
    data-name="{{ $row['name'] ?? '' }}"
    data-email="{{ $row['email'] ?? '' }}"
    data-phone="{{ $row['phone'] ?? '' }}"
    data-profile-image="{{ $profileImage }}">
    <td>
        <div class="d-flex align-items-center gap-2">
            <img src="{{ $profileImage }}" alt="" class="rounded-circle" width="40" height="40"
                 style="object-fit: cover;"
                 onerror="this.onerror=null;this.src='{{ userProfileImageUrl(null) }}';">
            <div class="small">
                <div class="fw-semibold">{{ $row['name'] ?? '-' }}</div>
                <div class="text-muted">{{ $row['email'] ?? '' }}</div>
                <div class="text-muted">{{ $row['phone'] ?? '' }}</div>
            </div>
        </div>
    </td>
    <td class="staffing-assigned-label">{{ $assigned ? 'Yes' : 'No' }}</td>
    <td>{{ $row['wage'] ?? '0.00' }}</td>
    <td class="staffing-overbooked-label {{ $overbooked ? 'text-danger fw-semibold' : '' }}">
        {{ $overbooked ? 'Yes' : 'No' }}
    </td>
    <td>
        <div class="d-flex flex-column gap-1 small">
            <label class="form-check mb-0">
                <input type="checkbox" class="form-check-input staffing-no-show" {{ $isNoShow ? 'checked' : '' }}>
                <span class="form-check-label text-uppercase">No Show</span>
            </label>
            <label class="form-check mb-0">
                <input type="checkbox" class="form-check-input staffing-pinata" {{ $hasPinata ? 'checked' : '' }}>
                <span class="form-check-label text-uppercase">Pinata</span>
            </label>
        </div>
    </td>
    <td>
        <div class="d-flex flex-wrap gap-1">
            @if (! $assigned)
                <button type="button" class="btn-ds btn-primary-ds btn-sm staffing-assign-btn">Assign</button>
            @endif
            <button type="button" class="btn-ds btn-danger-ds btn-sm staffing-remove-btn">Remove</button>
        </div>
    </td>
</tr>
