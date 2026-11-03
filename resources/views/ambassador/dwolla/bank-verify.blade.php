@extends('layout.app', ['page_name' => 'Verify Bank Account'])
<link rel="stylesheet" href="{{ asset('assets/select2/select2.css') }}">
@section('content')
    <div class="container p-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="h4">Verify Bank Account</div>
                <div class="fixed-border"></div>
            </div>
            <a href="{{ url()->previous() }}" class="btn btn-dark btn-sm">Back</a>
        </div>
        <div class="page-card-design">

            <p class="text-muted mt-2">
                We’ve sent two small deposits to your bank account.
                Enter the exact amounts to verify your account.
            </p>

            <form method="POST" action="{{ route('ambassador.dwolla.bank.verify') }}">
                @csrf

                <input type="hidden" name="funding_source_id" value="{{ $source->id }}">

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Deposit Amount 1</label>
                        <input type="number" step="0.01" name="amount1" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Deposit Amount 2</label>
                        <input type="number" step="0.01" name="amount2" class="form-control" required>
                    </div>
                </div>

                <button class="btn btn-primary mt-4">
                    Verify Bank Account
                </button>
            </form>
        </div>

    </div>

    </div>
@endsection
