@extends('layout.app', ['page_name' => 'Ambassadors Pending Approval'])
@section('content')
@php
    $rowCount = isset($rows) ? $rows->count() : 0;
@endphp
<div class="container py-2">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="h5 mb-1">Ambassadors Pending Approval</div>
            <p class="text-muted small mb-0">
                Approve or remove applications here, or open the event to use Pinata / No-Show modifiers.
            </p>
        </div>
        <span class="badge bg-secondary fs-6">{{ $rowCount }} pending</span>
    </div>
    <div class="fixed-border"></div>
    <div class="page-card-design">
        <div class="table-responsive mt-4">
            <table class="table table-striped table-bordered align-middle">
                <thead>
                    <tr class="text-uppercase small">
                        <th>Event</th>
                        <th>Profile</th>
                        <th>Event Date</th>
                        <th>Pay</th>
                        <th>Overbooked</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr data-assignment-id="{{ $row['assignment_id'] }}">
                            <td>
                                <a href="{{ $row['event_edit_url'] }}" class="fw-semibold text-decoration-none">
                                    {{ $row['event_name'] }}
                                </a>
                                <div class="text-muted small">Event #{{ $row['event_id'] }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $row['profile_image'] }}" alt="" class="rounded-circle"
                                         width="40" height="40" style="object-fit: cover;"
                                         onerror="this.onerror=null;this.src='{{ userProfileImageUrl(null) }}';">
                                    <div class="small">
                                        <div class="fw-semibold">{{ $row['name'] }}</div>
                                        <div class="text-muted">{{ $row['email'] }}</div>
                                        <div class="text-muted">{{ $row['phone'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">{{ $row['event_date'] }}</td>
                            <td>{{ $row['wage'] }}</td>
                            <td class="{{ $row['overbooked'] ? 'text-danger fw-semibold' : '' }}">
                                {{ $row['overbooked'] ? 'Yes' : 'No' }}
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button"
                                        class="btn-ds btn-primary-ds btn-sm btn-assign-pending"
                                        data-assignment-id="{{ $row['assignment_id'] }}">
                                        Assign
                                    </button>
                                    <button type="button"
                                        class="btn-ds btn-danger-ds btn-sm btn-remove-pending"
                                        data-assignment-id="{{ $row['assignment_id'] }}">
                                        Remove
                                    </button>
                                    <a href="{{ $row['event_edit_url'] }}"
                                       class="btn-ds btn-secondary-outline btn-sm">
                                        Open Event
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No pending ambassador applications.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const assignUrl = @json($assignUrl);
    const removeUrl = @json($removeUrl);
    const csrfToken = @json(csrf_token());

    function assignPending(assignmentId) {
        Swal.fire({
            title: 'Assign ambassador?',
            text: 'They will be marked as assigned for this event.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Assign',
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: assignUrl,
                method: 'POST',
                data: {
                    _token: csrfToken,
                    event_id: assignmentId,
                },
                success: function () {
                    Swal.fire('Assigned', 'Ambassador has been assigned to the event.', 'success')
                        .then(() => location.reload());
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Could not assign ambassador.';
                    Swal.fire('Error', msg, 'error');
                },
            });
        });
    }

    function removePending(assignmentId) {
        Swal.fire({
            title: 'Remove application?',
            text: 'This removes the ambassador from this event entirely.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Remove',
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }
            $.ajax({
                url: removeUrl,
                method: 'POST',
                data: {
                    _token: csrfToken,
                    assignment_id: assignmentId,
                },
                success: function () {
                    Swal.fire('Removed', 'Application has been removed.', 'success')
                        .then(() => location.reload());
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Could not remove application.';
                    Swal.fire('Error', msg, 'error');
                },
            });
        });
    }

    $(document).on('click', '.btn-assign-pending', function () {
        assignPending($(this).data('assignment-id'));
    });

    $(document).on('click', '.btn-remove-pending', function () {
        removePending($(this).data('assignment-id'));
    });
</script>
@endsection
