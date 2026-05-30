<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // 🔥 УБЕДИТЕСЬ В ЭТОЙ СТРОКЕ
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// 🔥 Класс ДОЛЖЕН имплементировать именно ShouldBroadcastNow вместо ShouldBroadcast
class SeoTaskFinished implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $taskId;
    public string $result;

    public function __construct(int $taskId, string $result)
    {
        $this->taskId = $taskId;
        $this->result = $result;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('seo-analysis'),
        ];
    }

    
}
