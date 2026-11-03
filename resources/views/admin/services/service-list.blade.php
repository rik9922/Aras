@extends('layout.app', ['page_name' => 'Services'])

@section('content')
    @php
        $user = auth()->user();
        $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
    @endphp
    <div class="container">
        <div class="page-card-design">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Services</h4>
                    <p class="text-muted small mb-0">Services are used as QuickBooks Invoice Line Items.</p>
                </div>
                <div class="d-flex gap-2">
                    <form action="{{ route($prefix.'.services.sync-to-quickbooks') }}" method="POST" id="syncServicesForm">
                        @csrf
                        <button type="submit" class="btn-ds btn-primary-ds" id="syncServicesBtn">
                            <i class="fas fa-sync-alt me-1"></i> Sync to QuickBooks
                        </button>
                    </form>
                    <a href="{{ route($prefix.'.services.create') }}" class="btn-ds btn-success-ds">
                        + Add Service
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Search --}}
            <div class="mb-3" style="max-width:360px;">
                <input type="text" id="serviceSearch" class="form-control form-control-sm"
                    placeholder="Search by name or description…">
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Service Name</th>
                            <th>Short Description</th>
                            <th class="text-nowrap" style="width:130px;">QuickBooks Status</th>
                            <th class="text-nowrap" style="width:110px;">QB Item ID</th>
                            <th class="text-nowrap" style="width:90px;">Status</th>
                            <th class="text-nowrap" style="width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTable">
                        @forelse($services as $index => $service)
                            <tr>
                                <td class="text-muted">{{ $index + 1 }}</td>

                                <td>
                                    <span class="fw-semibold">{{ $service->name }}</span>
                                    @php
                                        $mandatoryNames = array_map('mb_strtolower', array_keys(\App\Http\Controllers\Admin\ServiceController::SYSTEM_SERVICES));
                                    @endphp
                                    @if (in_array(mb_strtolower($service->name), $mandatoryNames, true))
                                        <span class="badge bg-secondary ms-1" style="font-size:9px;">System</span>
                                    @endif
                                </td>

                                <td class="text-muted small">
                                    {{ $service->description ?? '—' }}
                                </td>

                                <td class="text-nowrap">
                                    @if ($service->qb_item_id)
                                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Synced</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Not Synced</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($service->qb_item_id)
                                        <code class="small">{{ $service->qb_item_id }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-nowrap">
                                    @if ($service->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>

                                <td class="text-nowrap">
                                    {{-- Edit disabled: service name/description managed via QB Setup sync
                                    <a href="{{ route($prefix.'.services.edit', $service->id) }}"
                                        class="btn-ds btn-primary-outline btn-sm">
                                        Edit
                                    </a>
                                    --}}
                                    <form action="{{ route($prefix.'.services.toggle-status', $service) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit"
                                            class="btn-ds {{ $service->is_active ? 'btn-danger-outline' : 'btn-success-outline' }} btn-sm"
                                            onclick="return confirm('{{ $service->is_active ? 'Disable' : 'Enable' }} this service?')">
                                            {{ $service->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No services found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('serviceSearch').addEventListener('keyup', function () {
        const value = this.value.toLowerCase();
        document.querySelectorAll('#servicesTable tr').forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().includes(value) ? '' : 'none';
        });
    });
</script>
@endpush
