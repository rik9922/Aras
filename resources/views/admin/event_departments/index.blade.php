@extends('layout.app', ['page_name' => 'Event Department'])
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
            <div class="h5">Departments : <span class="total-data">{{ $departments->total() }}</span></div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            <div class="row align-items-center">
                <div class="col-md-9 pt-3">
                    <input type="search" class="form-control shadow-none" name="search"
                        placeholder="Search for a Department name here" id="searchInput">
                </div>
                <div class="col-md-3 pt-3">
                    <div class="d-grid">
                        <button type="button" class="btn-ds btn-primary-ds" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                            Add Department
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th><div>Department Name</div></th>
                            <th><div>Description</div></th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="department-table-body">
                        @if(!empty($departments) && count($departments) > 0)
                            @foreach ($departments as $department)
                                <tr>
                                    <td>{{ $department->name }}</td>
                                    <td>{{ $department->description }}</td>
                                    <td>
                                        <button type="button" class="btn-ds btn-secondary-ds btn-edit-department"
                                            data-id="{{ $department->id }}"
                                            data-name="{{ $department->name }}"
                                            data-description="{{ $department->description }}">
                                            <span>Edit</span>
                                        </button>
                                        <button type="button" class="btn-ds btn-danger-ds btn-delete-department ms-1"
                                            data-id="{{ $department->id }}"
                                            data-url="{{ route($routePrefix.'.delete.event-department', $department->id) }}">
                                            <span>Delete</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td class="text-center" colspan="3">Data Not Found.</td></tr>
                        @endif
                    </tbody>
                </table>
                <div class="d-flex justify-content-end mt-3">
                    {{ $departments->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Add Department Modal --}}
    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDepartmentModalLabel">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addDepartmentForm" action="{{ route($routePrefix.'.store.event-department') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="addDeptName" class="form-label text-uppercase">Department Name</label>
                            <input type="text" class="form-control shadow-none" name="name" id="addDeptName" placeholder="Name">
                            <div class="invalid-feedback" id="addDeptNameError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="addDeptDescription" class="form-label text-uppercase">Description</label>
                            <textarea class="form-control shadow-none" name="description" id="addDeptDescription" placeholder="Description"></textarea>
                            <div class="invalid-feedback" id="addDeptDescError"></div>
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

    {{-- Edit Department Modal --}}
    <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editDepartmentForm" action="{{ route($routePrefix.'.update.event-department') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="editDeptId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editDeptName" class="form-label text-uppercase">Department Name</label>
                            <input type="text" class="form-control shadow-none" name="name" id="editDeptName" placeholder="Name">
                            <div class="invalid-feedback" id="editDeptNameError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editDeptDescription" class="form-label text-uppercase">Description</label>
                            <textarea class="form-control shadow-none" name="description" id="editDeptDescription" placeholder="Description"></textarea>
                            <div class="invalid-feedback" id="editDeptDescError"></div>
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
    $('#searchInput').on('input', function () {
        loadDepartments($(this).val());
    });

    function loadDepartments(search) {
        $.ajax({
            url: "{{ route($routePrefix.'.event-department') }}",
            type: 'GET',
            data: { search: search },
            success: function (response) {
                var rows = '';
                if (response.departments && response.departments.length > 0) {
                    $.each(response.departments, function (i, dept) {
                        rows += '<tr>';
                        rows += '<td>' + dept.name + '</td>';
                        rows += '<td>' + (dept.description ?? '') + '</td>';
                        rows += '<td>'
                              + '<button type="button" class="btn-ds btn-secondary-ds btn-edit-department"'
                              + ' data-id="' + dept.id + '"'
                              + ' data-name="' + $('<div>').text(dept.name).html() + '"'
                              + ' data-description="' + $('<div>').text(dept.description ?? '').html() + '">'
                              + '<span>Edit</span></button> '
                              + '<button type="button" class="btn-ds btn-danger-ds btn-delete-department ms-1"'
                              + ' data-id="' + dept.id + '"'
                              + ' data-url="/{{ $routePrefix }}/delete-event-department/' + dept.id + '">'
                              + '<span>Delete</span></button>'
                              + '</td>';
                        rows += '</tr>';
                    });
                } else {
                    rows = '<tr><td colspan="3" class="text-center">Data not found</td></tr>';
                }
                $('#department-table-body').html(rows);
            }
        });
    }

    // --- Open Edit Modal ---
    $(document).on('click', '.btn-edit-department', function () {
        $('#editDeptId').val($(this).data('id'));
        $('#editDeptName').val($(this).data('name'));
        $('#editDeptDescription').val($(this).data('description'));
        clearErrors('edit');
        new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
    });

    // --- Reset Add Modal on close ---
    $('#addDepartmentModal').on('hidden.bs.modal', function () {
        $('#addDepartmentForm')[0].reset();
        clearErrors('add');
    });

    // --- AJAX Submit: Add ---
    $('#addDepartmentForm').on('submit', function (e) {
        e.preventDefault();
        clearErrors('add');
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('addDepartmentModal')).hide();
                $('#addDepartmentForm')[0].reset();
                showToast('success', 'Department added successfully!');
                loadDepartments('');
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showErrors('add', xhr.responseJSON.errors);
                } else {
                    showToast('error', 'An error occurred while adding the department.');
                }
            }
        });
    });

    // --- AJAX Submit: Edit ---
    $('#editDepartmentForm').on('submit', function (e) {
        e.preventDefault();
        clearErrors('edit');
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('editDepartmentModal')).hide();
                showToast('success', 'Department updated successfully!');
                loadDepartments('');
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    showErrors('edit', xhr.responseJSON.errors);
                } else {
                    showToast('error', 'An error occurred while updating the department.');
                }
            }
        });
    });

    // --- Delete Department ---
    $(document).on('click', '.btn-delete-department', function () {
        var url = $(this).data('url');
        var row = $(this).closest('tr');
        if (!confirm('Are you sure you want to delete this Department?')) return;
        $.ajax({
            url: url,
            type: 'POST',
            data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.success) {
                    row.remove();
                    showToast('success', res.message || 'Department deleted successfully!');
                } else {
                    showToast('error', res.message || 'Could not delete department.');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred.';
                showToast('error', msg);
            }
        });
    });

    function clearErrors(prefix) {
        var formId = '#' + prefix + 'DepartmentForm';
        $('.form-control', formId).removeClass('is-invalid');
        $('.invalid-feedback', formId).text('');
    }

    function showErrors(prefix, errors) {
        var formId = '#' + prefix + 'DepartmentForm';
        $.each(errors, function (field, messages) {
            var input = $(formId + ' [name="' + field + '"]');
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(messages[0]);
        });
    }
});
</script>
