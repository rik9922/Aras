<?php

namespace App\Console\Commands;

use App\Services\EventCheckInReadyNotificationService;
use Illuminate\Console\Command;

class NotifyEventCheckInReadyCommand extends Command
{
    protected $signature = 'events:notify-check-in-ready';

    protected $description = 'Notify ambassadors when assigned events reach check-in start time';

    public function handle(EventCheckInReadyNotificationService $service): int
    {
        $count = $service->processDueAssignments();
        $this->info("Check-in ready notifications sent for {$count} assignment(s).");

        return self::SUCCESS;
    }
}
