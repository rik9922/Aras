@php
    $ambassadorUser = auth()->user();
    $bankSetupStatus = $ambassadorUser && (int) $ambassadorUser->role_id === 5
        ? $ambassadorUser->getBankSetupStatus()
        : 'verified';
    $showBankSetupBanner = $bankSetupStatus === 'not_started';
    $showBankVerificationBanner = $bankSetupStatus === 'pending_verification';

    $bankSetupUrl = route('ambassador.dwolla.bank-account');
    if ($showBankSetupBanner && ! $ambassadorUser->dwollaCustomer) {
        $missingFields = app(\App\Services\Dwolla\CustomerService::class)
            ->getMissingDwollaFields($ambassadorUser);
        if ($missingFields !== []) {
            $bankSetupUrl = route('ambassador.dwolla.payment-setup');
        }
    }
@endphp
@if ($showBankSetupBanner)
    <div class="alert alert-warning mb-0 text-center py-2"
        style="border-radius: 0; background-color: #fff3cd; border-color: #ffc107;">
        <strong>Payment Setup Required</strong>
        <span class="ms-2">Please complete your Banking Management setup to receive payments.</span>
        <a href="{{ $bankSetupUrl }}" class="btn btn-sm btn-dark ms-2" style="padding: 4px 12px;">Complete Setup</a>
    </div>
@elseif ($showBankVerificationBanner)
    <div class="alert alert-info mb-0 text-center py-2"
        style="border-radius: 0;">
        <strong>Account Under Verification</strong>
    </div>
@endif
