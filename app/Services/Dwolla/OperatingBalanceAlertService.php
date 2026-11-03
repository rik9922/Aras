<?php

namespace App\Services\Dwolla;

use App\Models\DwollaBalanceAlertSnapshot;
use App\Models\Setting;
use App\Models\User;
use App\Services\Forecast\PayrollForecastService;
use App\Services\NotificationDispatchService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OperatingBalanceAlertService
{
    public function __construct(
        private readonly PayrollForecastService $forecastService,
        private readonly OperatingBalanceService $balanceService,
        private readonly NotificationDispatchService $notificationService
    ) {}

    public function evaluate(?int $days = null, bool $forceNotify = false): array
    {
        if (! (bool) config('dwolla_balance_alert.enabled', true)) {
            return ['enabled' => false];
        }

        $forecastDays = $days ?? (int) config('dwolla_balance_alert.forecast_days', 14);
        $forecast = $this->forecastService->forecastForNextDays($forecastDays);
        $balance = $this->balanceService->getOperatingBalance();

        $bufferAmount = (float) config('dwolla_balance_alert.buffer_amount', 0);
        $fixedFloorRaw = $this->resolveFixedFloorAmount();
        $fixedFloor = is_numeric($fixedFloorRaw) ? (float) $fixedFloorRaw : null;
        $forecastTotal = (float) $forecast['grand_total'];
        $dynamicMinimum = $forecastTotal + $bufferAmount;
        $requiredMinimum = $fixedFloor !== null ? max($fixedFloor, $dynamicMinimum) : $dynamicMinimum;

        $operatingBalance = (float) $balance['available_balance'];
        $shortfall = round($requiredMinimum - $operatingBalance, 2);
        $isBreach = $shortfall > 0;
        $severity = $isBreach ? $this->severityForShortfall($shortfall) : null;

        $snapshot = DwollaBalanceAlertSnapshot::create([
            'window_start' => $forecast['window_start'],
            'window_end' => $forecast['window_end'],
            'forecast_days' => $forecastDays,
            'forecast_total' => round($forecastTotal, 2),
            'buffer_amount' => round($bufferAmount, 2),
            'fixed_floor_amount' => $fixedFloor !== null ? round($fixedFloor, 2) : null,
            'required_minimum' => round($requiredMinimum, 2),
            'operating_balance' => round($operatingBalance, 2),
            'shortfall' => $shortfall > 0 ? $shortfall : 0,
            'currency' => (string) ($balance['currency'] ?? 'USD'),
            'is_breach' => $isBreach,
            'severity' => $severity,
            'meta' => [
                'forecast' => $forecast,
                'balance_as_of' => $balance['as_of'] ?? null,
            ],
        ]);

        $notified = false;
        if ($isBreach && ($forceNotify || ! $this->isInCooldown($severity))) {
            $notified = $this->notify($snapshot);
            if ($notified) {
                $snapshot->forceFill(['notified_at' => now()])->save();
            }
        }

        return [
            'enabled' => true,
            'snapshot_id' => $snapshot->id,
            'window_start' => $forecast['window_start'],
            'window_end' => $forecast['window_end'],
            'forecast_days' => $forecastDays,
            'forecast_total' => round($forecastTotal, 2),
            'buffer_amount' => round($bufferAmount, 2),
            'fixed_floor_amount' => $fixedFloor !== null ? round($fixedFloor, 2) : null,
            'required_minimum' => round($requiredMinimum, 2),
            'operating_balance' => round($operatingBalance, 2),
            'shortfall' => $shortfall > 0 ? $shortfall : 0,
            'is_breach' => $isBreach,
            'severity' => $severity,
            'notified' => $notified,
        ];
    }

    private function severityForShortfall(float $shortfall): string
    {
        $criticalGap = (float) config('dwolla_balance_alert.critical_gap', 10000);
        $warningGap = (float) config('dwolla_balance_alert.warning_gap', 0);

        if ($shortfall >= $criticalGap) {
            return 'critical';
        }

        if ($shortfall > $warningGap) {
            return 'warning';
        }

        return 'warning';
    }

    private function isInCooldown(?string $severity): bool
    {
        if ($severity === null) {
            return false;
        }

        $cooldownHours = (int) config('dwolla_balance_alert.cooldown_hours', 12);
        $threshold = Carbon::now()->subHours(max(1, $cooldownHours));

        return DwollaBalanceAlertSnapshot::query()
            ->where('is_breach', true)
            ->where('severity', $severity)
            ->whereNotNull('notified_at')
            ->where('notified_at', '>=', $threshold)
            ->exists();
    }

    private function notify(DwollaBalanceAlertSnapshot $snapshot): bool
    {
        $roleIds = array_values(array_unique(array_filter((array) config('dwolla_balance_alert.notify_role_ids', [1, 6]))));
        $explicitUserIds = array_values(array_unique(array_filter((array) config('dwolla_balance_alert.notify_user_ids', []))));

        $recipients = User::query()
            ->where('status', '1')
            ->where(function ($query) use ($roleIds, $explicitUserIds) {
                if (! empty($roleIds)) {
                    $query->whereIn('role_id', $roleIds);
                }
                if (! empty($explicitUserIds)) {
                    $query->orWhereIn('id', $explicitUserIds);
                }
            })
            ->get(['id']);

        if ($recipients->isEmpty()) {
            Log::warning('Dwolla operating balance alert skipped: no recipients configured');
            return false;
        }

        $currency = $snapshot->currency ?: 'USD';
        $severityLabel = strtoupper((string) $snapshot->severity);
        $title = "Dwolla Operating Balance Low ({$severityLabel})";
        $message = sprintf(
            'Operating balance %s %.2f is below required minimum %s %.2f (shortfall: %s %.2f).',
            $currency,
            (float) $snapshot->operating_balance,
            $currency,
            (float) $snapshot->required_minimum,
            $currency,
            (float) $snapshot->shortfall
        );

        $emailContent = sprintf(
            '<p><strong>Dwolla operating balance alert (%s)</strong></p><p>Current operating balance: <strong>%s %.2f</strong><br>Required minimum (%d-day forecast + buffer/floor): <strong>%s %.2f</strong><br>Forecast total: %s %.2f<br>Shortfall: <strong>%s %.2f</strong></p><p>Please add funds to the operating account before the next payroll/reimbursement sequence.</p>',
            e($severityLabel),
            e($currency),
            (float) $snapshot->operating_balance,
            (int) $snapshot->forecast_days,
            e($currency),
            (float) $snapshot->required_minimum,
            e($currency),
            (float) $snapshot->forecast_total,
            e($currency),
            (float) $snapshot->shortfall
        );

        foreach ($recipients as $recipient) {
            $this->notificationService->dispatch(
                null,
                (int) $recipient->id,
                'dwolla_balance_alert',
                $title,
                $message,
                $emailContent
            );
        }

        return true;
    }

    private function resolveFixedFloorAmount()
    {
        $fallback = config('dwolla_balance_alert.fixed_floor_amount');

        if (! Schema::hasTable('settings')) {
            return $fallback;
        }

        $setting = Setting::query()
            ->where('name', 'dwolla_balance_alert')
            ->value('value');

        if (! is_string($setting) || $setting === '') {
            return $fallback;
        }

        $decoded = json_decode($setting, true);
        $dbValue = data_get($decoded, 'fixed_floor_amount');

        return is_numeric($dbValue) ? $dbValue : $fallback;
    }
}
