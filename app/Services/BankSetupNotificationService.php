<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\BankingSetupPaymentSkippedNotification;
use App\Notifications\BankingSetupReminderNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BankSetupNotificationService
{
    public function __construct(
        private readonly NotificationDispatchService $notificationDispatch
    ) {}

    /**
     * @return array{ok:bool,sent:int,skipped:int,errors:int,disabled?:bool}
     */
    public function processAutomatedReminders(bool $dryRun = false): array
    {
        if (! config('banking_setup_reminders.enabled', true)) {
            return [
                'ok' => true,
                'sent' => 0,
                'skipped' => 0,
                'errors' => 0,
                'disabled' => true,
            ];
        }

        $intervalDays = max(1, (int) config('banking_setup_reminders.interval_days', 7));
        $graceDays = max(0, (int) config('banking_setup_reminders.grace_days_after_creation', 3));
        $reminderCutoff = now()->subDays($intervalDays);
        $creationCutoff = now()->subDays($graceDays);

        $sent = 0;
        $skipped = 0;
        $errors = 0;

        User::query()
            ->where('role_id', 5)
            ->where('status', '1')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('created_at', '<=', $creationCutoff)
            ->whereDoesntHave('dwollaCustomer.verifiedFundingSources')
            ->with(['dwollaCustomer'])
            ->chunkById(100, function ($ambassadors) use ($dryRun, $reminderCutoff, &$sent, &$skipped, &$errors) {
                foreach ($ambassadors as $ambassador) {
                    try {
                        if ($this->wasRemindedRecently((int) $ambassador->id, $reminderCutoff)) {
                            $skipped++;

                            continue;
                        }

                        if ($dryRun) {
                            $sent++;

                            continue;
                        }

                        if ($this->sendSetupReminder($ambassador)) {
                            $sent++;
                        } else {
                            $skipped++;
                        }
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::warning('Automated banking setup reminder failed', [
                            'ambassador_id' => $ambassador->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return [
            'ok' => $errors === 0,
            'sent' => $sent,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    public function sendSetupReminder(User $ambassador, ?int $senderId = null): bool
    {
        if ($ambassador->hasVerifiedBankAccount()) {
            return false;
        }

        $setupUrl = $this->bankSetupUrl($ambassador);
        $portalUrl = $this->portalUrl();

        $this->notificationDispatch->dispatch(
            $senderId,
            (int) $ambassador->id,
            'banking_setup_reminder',
            'Payment Setup Required',
            'Please complete your Banking Management setup to receive future payments.',
        );

        $ambassador->notify(new BankingSetupReminderNotification($setupUrl, $portalUrl));

        return true;
    }

    public function sendPaymentSkippedNotification(User $ambassador, ?int $senderId = null): void
    {
        if ($ambassador->hasVerifiedBankAccount()) {
            return;
        }

        $setupUrl = $this->bankSetupUrl($ambassador);
        $portalUrl = $this->portalUrl();

        $this->notificationDispatch->dispatch(
            $senderId,
            (int) $ambassador->id,
            'banking_setup_payment_skipped',
            'Action Required: Complete Direct Deposit Setup',
            'We attempted to process your payment, but your Banking Management setup is incomplete. Please complete your bank account setup to receive payment.',
        );

        $ambassador->notify(new BankingSetupPaymentSkippedNotification($setupUrl, $portalUrl));
    }

    private function portalUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    private function bankSetupUrl(User $ambassador): string
    {
        $customerService = app(\App\Services\Dwolla\CustomerService::class);

        if (! $ambassador->dwollaCustomer) {
            $missing = $customerService->getMissingDwollaFields($ambassador);
            if ($missing !== []) {
                return route('ambassador.dwolla.payment-setup');
            }
        }

        return route('ambassador.dwolla.bank-account');
    }

    private function wasRemindedRecently(int $ambassadorId, Carbon $cutoff): bool
    {
        if (! Schema::hasTable('notifications')) {
            return false;
        }

        return Notification::query()
            ->where('receiver_id', $ambassadorId)
            ->where('type', 'banking_setup_reminder')
            ->where('created_at', '>=', $cutoff)
            ->exists();
    }
}
