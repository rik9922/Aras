@extends('layout.app', ['page_name' => 'Distributor List'])
@php $routePrefix = request()->routeIs('manager.*') ? 'manager' : 'admin'; @endphp
@section('content')

    {{-- Toast Notifications --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="successMessage"></div>
        </div>
        <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="errorMessage"></div>
        </div>
    </div>

    <div class="container py-2">
        <div class="d-grid">
            <div class="h5"> Distributors : <span class="total-data">{{ $eventDistributor->total() }}</span></div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="row align-items-center">
                <div class="col-md-6 col-lg-8 pt-3">
                    <input type="search" class="form-control shadow-none" id="search" name="search"
                        placeholder="Search...">
                </div>
                <div class="col-md-3 col-lg-2 pt-3">
                    <div class="d-grid">
                        <button type="button" class="btn-ds btn-primary-ds" data-bs-toggle="modal" data-bs-target="#addDistributorModal">
                            Add Distributor
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th><div class="text-nowrap">Name</div></th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="distributor-table">
                        @if(!empty($eventDistributor) && count($eventDistributor) > 0)
                            @foreach ($eventDistributor as $distributor)
                                <tr>
                                    <td>{{ $distributor->name }}</td>
                                    <td>
                                        <button type="button" class="btn-ds btn-secondary-ds btn-edit-distributor"
                                            data-id="{{ $distributor->id }}"
                                            data-name="{{ $distributor->name }}">
                                            <span>Edit</span>
                                        </button>
                                        <button type="button" class="btn-ds btn-danger-ds btn-delete-distributor ms-1"
                                            data-id="{{ $distributor->id }}"
                                            data-url="{{ route($routePrefix.'.delete.event-destributor', $distributor->id) }}">
                                            <span>Delete</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="2" class="text-center">Data not found</td></tr>
                        @endif
                    </tbody>
                </table>
                <div class="d-flex justify-content-end mt-3">
                    {{ $eventDistributor->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Add Distributor Modal --}}
    <div class="modal fade" id="addDistributorModal" tabindex="-1" aria-labelledby="addDistributorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDistributorModalLabel">Add Distributor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addDistributorForm" action="{{ route($routePrefix.'.store.event-destributor') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="addDistName" class="form-label text-uppercase">Name</label>
                            <input type="text" class="form-control shadow-none" name="name" id="addDistName">
                            <div class="invalid-feedback" id="addDistNameError"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-ds btn-back" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-ds btn-save">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Distributor Modal --}}
    <div class="modal fade" id="editDistributorModal" tabindex="-1" aria-labelledby="editDistributorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDistributorModalLabel">Edit Distributor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editDistributorForm" action="{{ route($routePrefix.'.update.event-destributor') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="editDistId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editDistName" class="form-label text-uppercase">Name</label>
                            <input type="text" class="form-control shadow-none" name="name" id="editDistName">
                            <div class="invalid-feedback" id="editDistNameError"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-ds btn-back" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-ds btn-save">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script>
$(document).ready(function () {

    // --- Toast notification (matches event/index.blade.php pattern) ---
    function showToast(type, message) {
        var toastEl = document.getElementById(type + 'Toast');
        var msgEl   = document.getElementById(type + 'Message');
        if (!toastEl || !msgEl) return;
        msgEl.textContent = message;
        var toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
    }

    // --- Search ---
    function loadDistributors() {
        $.ajax({
            url: '{{ route($routePrefix.".event-destributor") }}',
            method: 'GET',
            data: { search: $('#search').val() },
            success: function (data) {
                $('#distributor-table').html(data.html);
            }
        });
    }

    $('#search').on('change keyup', loadDistributors);
    $('#search').on('input', function () {
        if ($(this).val() === '') loadDistributors();
    });

    // --- Open Edit Modal ---
    $(document).on('click', '.btn-edit-distributor', function () {
        $('#editDistId').val($(this).data('id'));
        $('#editDistName').val($(this).data('name'));
        clearDistErrors('edit');
        new bootstrap.Modal(document.getElementById('editDistributorModal')).show();
    });

    // --- Reset Add Modal on close ---
    $('#addDistributorModal').on('hidden.bs.modal', function () {
        $('#addDistributorForm')[0].reset();
        clearDistErrors('add');
    });

    // --- AJAX Submit: Add ---
    $('#addDistributorForm').on('submit', function (e) {
        e.preventDefault();
        clearDistErrors('add');
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('addDistributorModal')).hide();
                $('#addDistributorForm')[0].reset();
                showToast('success', 'Distributor added successfully!');
                loadDistributors();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showDistErrors('add', xhr.responseJSON.errors);
                } else {
                    showToast('error', 'An error occurred while adding the distributor.');
                }
            }
        });
    });

    // --- AJAX Submit: Edit ---
    $('#editDistributorForm').on('submit', function (e) {
        e.preventDefault();
        clearDistErrors('edit');
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('editDistributorModal')).hide();
                showToast('success', 'Distributor updated successfully!');
                loadDistributors();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showDistErrors('edit', xhr.responseJSON.errors);
                } else {
                    showToast('error', 'An error occurred while updating the distributor.');
                }
            }
        });
    });

    // --- Delete Distributor ---
    $(document).on('click', '.btn-delete-distributor', function () {
        var url = $(this).data('url');
        var row = $(this).closest('tr');
        if (!confirm('Are you sure you want to delete this Distributor?')) return;
        $.ajax({
            url: url,
            type: 'POST',
            data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.success) {
                    row.remove();
                    showToast('success', res.message || 'Distributor deleted successfully!');
                } else {
                    showToast('error', res.message || 'Could not delete distributor.');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred.';
                showToast('error', msg);
            }
        });
    });

    function clearDistErrors(prefix) {
        var form = prefix === 'add' ? '#addDistributorForm' : '#editDistributorForm';
        $('.form-control', form).removeClass('is-invalid');
        $('.invalid-feedback', form).text('');
    }

    function showDistErrors(prefix, errors) {
        var form = prefix === 'add' ? '#addDistributorForm' : '#editDistributorForm';
        $.each(errors, function (field, messages) {
            var input = $(form + ' [name="' + field + '"]');
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(messages[0]);
        });
    }
});
</script>
