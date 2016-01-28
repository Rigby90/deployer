<?php

namespace REBELinBLUE\Deployer\Events;

use REBELinBLUE\Deployer\Heartbeat;
use REBELinBLUE\Deployer\Events\Event;
use Illuminate\Queue\SerializesModels;

/**
 * Event class which is thrown when the heartbeat recovers
 **/
class HeartbeatRecovered extends Event
{
    use SerializesModels;

    public $heartbeat;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Heartbeat $heartbeat)
    {
        $this->heartbeat = $heartbeat;
    }
}
