<?php

namespace Biigle\Modules\Geo\Events;

use Biigle\User;
use Biigle\Broadcasting\UserChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class GeoTiffUploadFailed implements ShouldBroadcastNow
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user that created the server.
     *
     * @var User
     */
    public $user;

    /**
     * Error message
     *
     * @var string
     */
    protected $message;

    /**
     * Create a new event instance.
     *
     * @param User $user
     *
     * @return void
     */
    public function __construct(User $user, string $message)
    {
        $this->user = $user;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new UserChannel($this->user);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'error' => $this->message
        ];
    }
}
