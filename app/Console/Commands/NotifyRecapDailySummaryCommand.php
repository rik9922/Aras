<?php

namespace App\Console\Commands;

use App\Services\RecapDailySummaryNotificationService;
use Illuminate\Console\Command;

class NotifyRecapDailySummaryCommand extends Command
{
    protected $signature = 'recaps:notify-daily-summary {--dry-run : Count pending recaps without sending email or saving marks} {--json : Output JSON summary}';

    protected $description = 'Send managers a daily summary of newly submitted recaps from the previous 24 hours';

    public function handle(RecapDailySummaryNotificationService $service): int
    {
        $result = $service->run(asOf: now(), dryRun: (bool) $this->option('dry-run'));

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            if ($result['skipped'] ?? false) {
                $this->info(sprintf(
                    'No summary sent (%d pending recaps in window %s to %s).',
                    (int) ($result['recap_count'] ?? 0),
                    (string) ($result['window_start'] ?? '-'),
                    (string) ($result['window_end'] ?? '-')
                ));
            } else {
                $this->info(sprintf(
                    'Sent daily recap summary for %d recap(s) to %d manager(s).',
                    (int) ($result['recap_count'] ?? 0),
                    (int) ($result['managers_notified'] ?? 0)
                ));
            }
        }

        return ($result['ok'] ?? true) ? self::SUCCESS : self::FAILURE;
    }
}
