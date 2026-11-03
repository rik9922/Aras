<?php

namespace App\Console\Commands;

use App\Services\BankSetupNotificationService;
use Illuminate\Console\Command;

class SendBankingSetupRemindersCommand extends Command
{
    protected $signature = 'banking:send-setup-reminders {--dry-run : Evaluate reminders without sending} {--json : Output JSON summary}';

    protected $description = 'Send automated banking setup reminders to active ambassadors without verified bank accounts';

    public function handle(BankSetupNotificationService $service): int
    {
        $result = $service->processAutomatedReminders((bool) $this->option('dry-run'));

        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            if ($result['disabled'] ?? false) {
                $this->warn('Automated banking setup reminders are disabled.');
            } elseif (! ($result['ok'] ?? true)) {
                $this->warn('Some automated reminders failed.');
            }

            $this->info(sprintf(
                'Sent: %d | Skipped: %d | Errors: %d',
                (int) ($result['sent'] ?? 0),
                (int) ($result['skipped'] ?? 0),
                (int) ($result['errors'] ?? 0)
            ));
        }

        return ($result['ok'] ?? true) ? self::SUCCESS : self::FAILURE;
    }
}
