<?php
namespace App\Listeners;
use App\Events\RecapApprovedForPayout;
use App\Jobs\InitiateAmbassadorPayout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
class QueueAmbassadorPayout implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }
    /**
     * Handle the event.
     */
    public function handle(RecapApprovedForPayout $event): void
    {
        $payout = $event->payout;
        if ($payout->status !== 'approved') {
            return;
        }
        $payout->update([
            'status'    => 'queued',
            'queued_at' => now(),
        ]);
        InitiateAmbassadorPayout::dispatch($payout)->onQueue('payouts');
    }
}