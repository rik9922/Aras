<?php

namespace App\Console\Commands;

use App\Services\LateCheckInNotificationService;
use Illuminate\Console\Command;

class NotifyLateCheckInsCommand extends Command
{
    protected $signature = 'events:notify-late-check-ins';

    protected $description = 'Notify managers and ambassadors when check-in is late after event start';

    public function handle(LateCheckInNotificationService $service): int
    {
        $count = $service->processDueAssignments();
        $this->info("Late check-in notifications sent for {$count} assignment(s).");

        return self::SUCCESS;
    }
}
