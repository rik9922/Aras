@extends('layout.app', ['page_name' => 'Add Service'])

@section('content')
    @php
        $user = auth()->user();
        $prefix = $user && $user->role_id == 6 ? 'manager' : 'admin';
    @endphp
    <div class="container">
        <div class="page-card-design">

            <h4 class="mb-1">Add Service</h4>
            <p class="text-muted small mb-4">Custom services are used as QuickBooks Invoice Line Items.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route($prefix.'.services.store') }}">
                @csrf

                <div class="row">

                    {{-- Service Name --}}
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" placeholder="e.g. Ice Fee">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Short Description --}}
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold">Short Description</label>
                        <textarea name="description" rows="2"
                            class="form-control @error('description') is-invalid @enderror"
                            placeholder="Brief description synced to QuickBooks as the line item description.">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">This description is sent to QuickBooks when the service is synced.</div>
                    </div>

                </div>

                {{-- Actions --}}
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route($prefix.'.services.index') }}" class="btn-ds btn-back">
                        Cancel
                    </a>
                    <button type="submit" class="btn-ds btn-primary-ds">
                        Save Service
                    </button>
                </div>

            </form>

        </div>
    </div>
@endsection
