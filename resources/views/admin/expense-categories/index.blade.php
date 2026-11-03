@extends('layout.app', ['page_name' => 'Expense Categories'])

@section('content')
    @php
        $user = auth()->user();
        $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
    @endphp
    <div class="container py-3">
        <h5>Expense Categories</h5>
        <div class="fixed-border"></div>

        <div class="content-box-grey d-flex align-items-center gap-2 p-2 mb-3">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                These categories create and link QuickBooks Expense accounts. The default category is used when creating
                ambassador's payouts.
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="page-card-design mb-3">
            <form method="POST" action="{{ route($prefix.'.expense-categories.store') }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Detail Type</label>
                        <select name="detail_type" class="form-select" required>
                            <option value="">Select detail type</option>
                            @foreach ($detailTypes as $code => $label)
                                <option value="{{ $code }}" {{ old('detail_type') === $code ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Description (optional)</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn-ds btn-success-ds">
                            Add &amp; Sync to QuickBooks
                        </button>
                    </div>
                </div>
                @if ($errors->any())
                    <div class="mt-2 text-danger">
                        {{ $errors->first() }}
                    </div>
                @endif
            </form>
        </div>

        <div class="page-card-design">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Detail Type</th>
                            <th>Expense Account ID</th>
                            <th>Default</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $i => $category)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->description ?? '-' }}</td>
                                <td>{{ $category->detail_type ?? '-' }}</td>
                                <td>{{ $category->quickbooks_account_id ?? '-' }}</td>
                                <td>
                                    @if ($category->is_default)
                                        <span class="badge bg-success">Default</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td>
                                    @unless ($category->is_default)
                                        <form method="POST"
                                            action="{{ route($prefix.'.expense-categories.default', $category) }}"
                                            class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-ds btn-primary-outline btn-sm">
                                                Make Default
                                            </button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No expense categories yet. Create one above to start syncing ambassador expenses.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
