<?php

namespace App\Jobs;

use App\Services\Dwolla\PayoutRetryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryFailedPayoutsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        // constructor empty
    }

    public function handle()
    {
        // Service yahan resolve karo
        app(PayoutRetryService::class)->retryFailedPayouts();
        \Log::info('RetryFailedPayoutsJob completed.');
    }
}
