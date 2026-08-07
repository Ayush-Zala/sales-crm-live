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
    public ?int $managerId;

    /**
     * @param string   $type       'sale' | 'assign' | 'unassign'
     * @param int|null $managerId  The manager whose team triggered this. Null = global.
     */
    public function __construct(
        string $type,
        ?int $managerId
    ) {
        $this->type          = $type;
        $this->managerId     = $managerId;
    }

    /**
     * Broadcast on the appropriate private channels:
     *  - Always on the global admin channel
     *  - And on the manager's personal channel (if managerId is set)
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('dashboard.global'),
        ];

        if ($this->managerId) {
            $channels[] = new PrivateChannel("dashboard.manager.{$this->managerId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'DashboardStatsUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'type'           => $this->type,
            'manager_id'     => $this->managerId,
        ];
    }
}
