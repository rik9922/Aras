@extends('layout.app', ['page_name' => 'Edit Service'])

@section('content')
    @php
        $user = auth()->user();
        $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
    @endphp
    <div class="container">
        <div class="page-card-design">

            <h4 class="mb-1">Edit Service</h4>
            <p class="text-muted small mb-4">Changes to Name and Description will be synced to QuickBooks if this service is already linked.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route($prefix.'.services.update', $service) }}">
                @csrf
                @method('PUT')

                <div class="row">

                    {{-- Service Name --}}
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $service->name) }}" placeholder="e.g. Ice Fee">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- QuickBooks status indicator --}}
                    <div class="col-md-4 mb-3 d-flex align-items-end pb-1">
                        @if ($service->qb_item_id)
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Synced to QuickBooks
                            </span>
                        @else
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-exclamation-circle me-1"></i>Not yet synced
                            </span>
                        @endif
                    </div>

                    {{-- Short Description --}}
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold">Short Description</label>
                        <textarea name="description" rows="2"
                            class="form-control @error('description') is-invalid @enderror"
                            placeholder="Brief description synced to QuickBooks as the line item description.">{{ old('description', $service->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">This description is sent to QuickBooks when the service is synced.</div>
                    </div>

                </div>

                {{-- Actions --}}
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route($prefix.'.services.index') }}" class="btn-ds btn-secondary-ds">
                        Cancel
                    </a>
                    <button type="submit" class="btn-ds btn-primary-ds">
                        Update Service
                    </button>
                </div>

            </form>

        </div>
    </div>
@endsection
