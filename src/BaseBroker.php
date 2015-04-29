<?php

namespace limitium\zmq;


abstract class BaseBroker
{
    /**
     * @var \ZMQContext
     */
    protected $context;

    /**
     * @var \ZMQSocket
     */
    protected $socket;

    /**
     * @var string Broker endpoint
     */
    protected $endpoint;

    protected $verbose;

    public function __construct($endpoint, \ZMQContext $context = null, $verbose = false)
    {
        if (!$context) {
            $context = new \ZMQContext();
        }
        $this->context = $context;
        $this->endpoint = $endpoint;
        $this->verbose = $verbose;
    }

    protected function createSocket($socketType, array $options = [])
    {
        $this->socket = $this->context->getSocket($socketType);
        foreach ($options as $key => $value) {
            $this->socket->setSockOpt($key, $value);
        }
    }
}