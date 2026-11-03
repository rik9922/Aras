<?php

use App\Models\AssignAmbassador;
use App\Models\Market;
use App\Models\Recap;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

if (! function_exists('getUserDashboard')) {
    function getUserDashboard()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->role->name == 'Administrator') {
                return 'admin.events';
            } elseif ($user && $user->role->name == 'Client') {
                return 'client.calendar';
            } elseif ($user && $user->role->name == 'Location') {
                return 'location.calendar';
            } elseif ($user && $user->role->name == 'Brand') {
                return 'brand.events';
            } elseif ($user && $user->role->name == 'Ambassador') {
                return 'ambassador.calendar';
            } elseif ($user && $user->role->name == 'Manager') {
                return 'manager.events';
            }
        }

        return 'login';
    }
}

if (! function_exists('googleMapsSettingValue')) {
    function googleMapsSettingValue(string $key): ?string
    {
        if (! Schema::hasTable('settings')) {
            return null;
        }

        $raw = \App\Models\Setting::where('name', 'map')->value('value');
        $settings = json_decode((string) $raw, true);

        if (! is_array($settings)) {
            return null;
        }

        $value = trim((string) ($settings[$key] ?? ''));

        return $value === '' ? null : $value;
    }
}

if (! function_exists('googleMapsPublicKey')) {
    function googleMapsPublicKey(): string
    {
        return googleMapsSettingValue('public_key') ?? (string) config('app.googleAPIkey');
    }
}

if (! function_exists('googleMapsServerKey')) {
    function googleMapsServerKey(): string
    {
        return googleMapsSettingValue('server_key')
            ?? googleMapsSettingValue('public_key')
            ?? (string) config('app.googleAPIkey');
    }
}

if (! function_exists('ambassadorName')) {
    function ambassadorName($eventId)
    {
        $getAmbassador = AssignAmbassador::where(['event_id' => $eventId, 'status' => '1'])->pluck('ambassador_id')->toArray();
        if ($getAmbassador) {
            $ambData = User::whereIn('id', $getAmbassador)->pluck('name')->toArray();
            $name = implode(',', $ambData);

            return $name;
        }

        return '-';
    }
}

if (! function_exists('getState')) {
    function getState()
    {
        $states = State::get();

        return $states;
    }
}

if (! function_exists('getMarket')) {
    function getMarket()
    {
        $markets = Market::get();

        return $markets;
    }
}

if (! function_exists('getExistMarket')) {
    function getExistMarket($marketId)
    {
        if (is_array($marketId)) {
            $markets = Market::whereIn('id', $marketId)->get();

            return $markets;
        }

        return [];
    }
}

if (! function_exists('getRecap')) {
    function getRecap($ambassadorId, $eventId)
    {
        $recap = Recap::where('ambassador_id', $ambassadorId)->where('event_id', $eventId)->first();

        return $recap;
    }
}

if (! function_exists('assignedAmbassador')) {
    function assignedAmbassador($eventId)
    {
        $assigned = AssignAmbassador::where('ambassador_id', Auth::id())->where('event_id', $eventId)->first();

        return $assigned;
    }
}

if (! function_exists('getAssignedAmbassadorsByEvent')) {
    function getAssignedAmbassadorsByEvent($eventId)
    {
        $assigned = AssignAmbassador::where('event_id', $eventId)->where('status', '1')->get();

        return $assigned;
    }
}

if (! function_exists('getAssignAmbassador')) {
    function getAssignAmbassador($eventId, $ambassadorId)
    {
        $assigned = AssignAmbassador::where('ambassador_id', $ambassadorId)->where('event_id', $eventId)->where('status', '1')->first();

        return $assigned;
    }
}

if (! function_exists('getUserByEvent')) {
    function getUserByEvent($eventId)
    {

        $assigned = AssignAmbassador::where('ambassador_id', Auth::id())->where('event_id', $eventId)->first();
        if ($assigned) {
            $user = User::where('id', Auth::id())->first();

            return $user;
        }
    }
}

if (! function_exists('getAmbassadorWage')) {
    function getAmbassadorWage()
    {
        return User::where('id', Auth::id())->value('wage');
    }
}

if (! function_exists('getAmbassadorWageAmout')) {
    function getAmbassadorWageAmout($ambassadorId)
    {
        return User::where('id', $ambassadorId)->value('wage');
    }
}

if (! function_exists('getUserNameByQBCustomerId')) {
    function getUserNameByQBCustomerId($qbCustomerId)
    {
        $client = \App\Models\Client::where('quickbooks_customer_id', $qbCustomerId)
            ->with('user:id,name')
            ->first();

        return $client?->user?->name;
    }
}

if (!function_exists('getShirtSize')) {
    function getShirtSize($key = null)
    {
        $shirtSizes = [
            'Small' => 'Small',
            'Medium' => 'Medium',
            'Large' => 'Large',
            'Extra Large' => 'Extra Large',
        ];

        return $key !== null
            ? ($shirtSizes[$key] ?? null)
            : $shirtSizes;
    }
}

if (! function_exists('getAmount')) {
    function getAmount($amount, $duration, $overridePay)
    {
        $timeUsed = $duration;
        $hourlyRate = $amount;

        [$hours, $minutes, $seconds] = explode(':', $timeUsed);
        $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

        $totalHours = $totalSeconds / 3600;

        $totalAmount = $totalHours * $hourlyRate;

        $totalAmount = round($totalAmount, 2);

        return ((int) $totalAmount + (int) $overridePay) ?? 0.00;
    }
}

if (! function_exists('formatStatus')) {
    function formatStatus(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}

if (! function_exists('userProfileImageUrl')) {
    function userProfileImageUrl(?string $profileImage): string
    {
        if (empty($profileImage)) {
            return asset('assets/images/profile.jpg');
        }

        return asset($profileImage);
    }
}
