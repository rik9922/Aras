<?php

namespace App\Console\Commands;

use App\Services\CheckInDiscrepancyDigestService;
use Illuminate\Console\Command;

class NotifyCheckInDiscrepancyDigestCommand extends Command
{
    protected $signature = 'events:notify-check-in-discrepancy-digest';
    protected $description = 'Send managers a daily review of late or off-site check-ins';

    public function handle(CheckInDiscrepancyDigestService $service): int
    {
        $count = $service->sendDailyDigest();
        $this->info("Check-in discrepancy digest sent to {$count} manager(s).");
        return self::SUCCESS;
    }
}
