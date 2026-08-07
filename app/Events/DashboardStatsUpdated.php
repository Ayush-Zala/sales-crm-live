<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardStatsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;
    public int $managerId;
    public int $count;

    /**
     * Create a new event instance.
     *
     * @param string $type The event type: 'sale', 'assign', 'unassign'
     * @param int $managerId The ID of the manager responsible (for localized updates)
     * @param int $count The number of items affected
     */
    public function __construct(string $type, int $managerId, int $count = 1)
    {
        $this->type = $type;
        $this->managerId = $managerId;
        $this->count = $count;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to a global channel for admins, and a manager-specific channel
        return [
            new PrivateChannel('dashboard.global'),
            new PrivateChannel('dashboard.manager.' . $this->managerId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'DashboardStatsUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'type'       => $this->type,
            'manager_id' => $this->managerId,
            'count'      => $this->count,
        ];
    }
}
