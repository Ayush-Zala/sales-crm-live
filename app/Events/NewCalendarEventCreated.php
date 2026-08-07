<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCalendarEventCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $managerId;
    public array $event;

    /**
     * @param int   $managerId  The reporting_authority_id of the user who created the event
     * @param array $event      The calendar event data to push to the frontend
     */
    public function __construct(int $managerId, array $event)
    {
        $this->managerId = $managerId;
        $this->event     = $event;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard.global'),
            new PrivateChannel("dashboard.manager.{$this->managerId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewCalendarEventCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'manager_id' => $this->managerId,
            'event'      => $this->event,
        ];
    }
}
