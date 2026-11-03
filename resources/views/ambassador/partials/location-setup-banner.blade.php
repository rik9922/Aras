@php
    $ambassadorUser = auth()->user();
    $showLocationBanner = $ambassadorUser
        && (int) $ambassadorUser->role_id === 5
        && (empty($ambassadorUser->latitude) || empty($ambassadorUser->longitude));
@endphp
@if ($showLocationBanner)
    <div class="alert alert-warning mb-0 text-center py-2"
        style="border-radius: 0; background-color: #fff3cd; border-color: #ffc107;">
        <strong>Location Required</strong>
        <span class="ms-2">Please select your location on the map in your profile.</span>
        <a href="{{ route('ambassador.profile') }}" class="btn btn-sm btn-dark ms-2" style="padding: 4px 12px;">Update Profile</a>
    </div>
@endif
