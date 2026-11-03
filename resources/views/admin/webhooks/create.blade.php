@extends('layout.app', ['page_name' => 'Add Webhook Subscription'])

@section('content')
    <div class="container py-3">
        <div class="d-flex">
            <div class="h5">Dwolla Webhook Subscription</div>
        </div>
        <div class="fixed-border"></div>
        <div class="page-card-design">
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('admin.webhooks.store') }}" method="POST">
                @csrf
                <div class="row p-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="url" class="form-label text-uppercase">Webhook URL</label>
                            <input type="url" class="form-control shadow-none @error('url') is-invalid @enderror"
                                name="url" id="url" value="{{ old('url', $defaultUrl) }}" placeholder="https://your-domain.com/api/webhooks/dwolla">
                            <small class="text-muted d-block">Dwolla will POST transfer events (e.g. transfer_completed, transfer_failed) to this URL.</small>
                            <small class="text-warning d-block mt-1">Dwolla does not accept localhost URLs. Use a publicly accessible URL (e.g. ngrok for local dev, or your staging/production domain).</small>
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="secret" class="form-label text-uppercase">Secret (optional)</label>
                            <input type="text" class="form-control shadow-none @error('secret') is-invalid @enderror"
                                name="secret" id="secret" value="{{ old('secret') }}" placeholder="Leave blank to use DWOLLA_WEBHOOK_SECRET from .env">
                            @error('secret')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="col-12 p-3">
                    <button type="submit" class="btn-ds btn-primary-ds">Create Subscription</button>
                    <a href="{{ route('admin.webhooks.index') }}" class="btn-ds btn-secondary-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
