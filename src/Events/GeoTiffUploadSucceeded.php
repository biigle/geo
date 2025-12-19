<?php

namespace Biigle\Modules\Geo\Events;

use Biigle\User;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Broadcasting\UserChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class GeoTiffUploadSucceeded implements ShouldBroadcastNow
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user that created the server.
     *
     * @var User
     */
    public $user;

    public $overlay;

    /**
     * Ignore this event if the overlay does not exist any more.
     *
     * @var bool
     */
    protected $deleteWhenMissingModels = true;

    /**
     * Create a new event instance.
     *
     * @param GeoOverlay $annotation
     * @param User $user
     * @return void
     */
    public function __construct(GeoOverlay $overlay, User $user)
    {
        $this->overlay = $overlay;
        $this->user = $user;
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
        $this->overlay->processed = true;
        $this->overlay->save();

        return [
            'overlay' => $this->overlay,
        ];
    }
}