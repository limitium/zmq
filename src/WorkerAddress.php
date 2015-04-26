<?php

namespace limitium\zmq;

/**
 * For internal use
 *
 * Class WorkerAddress
 * @package limitium\zmq
 */
class WorkerAddress
{
    public $address;
    public $expiry;

    /**
     * @param $address Worker zmq address
     */
    public function __construct($address)
    {
        $this->address = $address;
    }

    public function aliveFor($time)
    {
        $this->expiry = microtime(1) + $time / 1000;
    }
}