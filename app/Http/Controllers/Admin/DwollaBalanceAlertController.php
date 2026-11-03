<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DwollaBalanceAlertSnapshot;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DwollaBalanceAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = DwollaBalanceAlertSnapshot::query()->orderByDesc('created_at');

        if ($request->filled('breach')) {
            $query->where('is_breach', $request->breach === '1');
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $snapshots = $query->paginate(20)->withQueryString();
        $latest = DwollaBalanceAlertSnapshot::query()->latest('created_at')->first();
        $activeBottomLineLimit = $this->resolveActiveBottomLineLimit();

        return view('admin.dwolla.balance-alerts', [
            'snapshots' => $snapshots,
            'latest' => $latest,
            'activeBottomLineLimit' => $activeBottomLineLimit,
            'isManager' => $request->routeIs('manager.*'),
        ]);
    }

    private function resolveActiveBottomLineLimit(): ?float
    {
        $fallback = config('dwolla_balance_alert.fixed_floor_amount');
        $value = is_numeric($fallback) ? (float) $fallback : null;

        if (! Schema::hasTable('settings')) {
            return $value;
        }

        $raw = Setting::query()
            ->where('name', 'dwolla_balance_alert')
            ->value('value');

        if (! is_string($raw) || $raw === '') {
            return $value;
        }

        $decoded = json_decode($raw, true);
        $dbValue = data_get($decoded, 'fixed_floor_amount');

        return is_numeric($dbValue) ? (float) $dbValue : $value;
    }
}
