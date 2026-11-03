<?php

namespace App\Events;

use App\Models\AmbassadorPayout;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecapApprovedForPayout
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AmbassadorPayout $payout;

    /**
     * Create a new event instance.
     */
    public function __construct(AmbassadorPayout $payout)
    {
        $this->payout = $payout;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
