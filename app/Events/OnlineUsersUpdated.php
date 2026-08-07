<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnlineUsersUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $onlineCount;
    public int $totalUsers;

    public function __construct(int $onlineCount, int $totalUsers)
    {
        $this->onlineCount = $onlineCount;
        $this->totalUsers  = $totalUsers;
    }

    public function broadcastOn(): array
    {
        // Online users is an admin-only metric — broadcast globally
        return [
            new PrivateChannel('dashboard.global'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OnlineUsersUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'online_count' => $this->onlineCount,
            'total_users'  => $this->totalUsers,
        ];
    }
}
