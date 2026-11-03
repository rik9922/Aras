<?php

namespace App\Console\Commands;

use App\Services\InvoiceMonthMilestoneNotificationService;
use Illuminate\Console\Command;

class NotifyInvoiceMonthMilestonesCommand extends Command
{
    protected $signature = 'invoices:notify-month-milestones {--month= : YYYY-MM (defaults to current month)} {--dry-run : Do not persist marks or send notifications} {--json : Output JSON summary}';

    protected $description = 'Notify admins/managers when invoice-month milestones are reached (recaps complete / no upcoming events)';

    public function handle(InvoiceMonthMilestoneNotificationService $service): int
    {
        $month = $this->option('month') ? (string) $this->option('month') : null;
        $dryRun = (bool) $this->option('dry-run');

        $result = $service->run($month, $dryRun);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            if (! ($result['ok'] ?? true)) {
                $this->warn($result['message'] ?? 'Some notifications failed');
            }
            $this->info(sprintf(
                'Month: %s | Clients: %d | Recaps notified: %d | No-upcoming notified: %d | Skipped: %d | Errors: %d',
                (string) ($result['month'] ?? '-'),
                (int) ($result['clients_evaluated'] ?? 0),
                (int) ($result['all_recaps_notified'] ?? 0),
                (int) ($result['no_upcoming_notified'] ?? 0),
                (int) ($result['skipped'] ?? 0),
                (int) ($result['errors'] ?? 0)
            ));
        }

        return ($result['ok'] ?? true) ? self::SUCCESS : self::FAILURE;
    }
}

