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

    /**
     * @param $socketType
     * @param array $options
     * @param bool $justCreate
     * @return \ZMQSocket
     */
    protected function createSocket($socketType, array $options = [], $justCreate = false)
    {
        $socket = $this->context->getSocket($socketType);
        foreach ($options as $key => $value) {
            $socket->setSockOpt($key, $value);
        }
        if (!$justCreate) {
            $this->socket = $socket;
        }
        return $socket;
    }
}