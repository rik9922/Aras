<?php

namespace App\Console\Commands;

use App\Services\Ambassador\FirstShiftManagerAlertService;
use Illuminate\Console\Command;

class NotifyEventMarketManagerFirstShiftCommand extends Command
{
    protected $signature = 'ambassador-first-shift:notify-managers {--json : Print JSON summary}';

    protected $description = 'Send a one-time push to the Event Market Manager two days before a Brand Ambassador\'s first confirmed shift';

    public function handle(FirstShiftManagerAlertService $service): int
    {
        $result = $service->run();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            if (! ($result['ok'] ?? true)) {
                $this->warn($result['message'] ?? 'Failed');
            } else {
                $this->info(sprintf(
                    'Notified: %d | Suppressed: %d | Still pending (wait for trigger day): %d',
                    (int) ($result['notified'] ?? 0),
                    (int) ($result['suppressed'] ?? 0),
                    (int) ($result['pending'] ?? 0)
                ));
            }
        }

        return self::SUCCESS;
    }
}
