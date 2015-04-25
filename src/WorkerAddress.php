<?php

namespace limitium\zmq;


class WorkerAddress
{
    public $address;
    public $expiry;

    public function __construct($address)
    {
        $this->address = $address;
    }

    public function aliveFor($time)
    {
        $this->expiry = microtime(1) + $time / 1000;
    }
}