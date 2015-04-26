<?php

namespace limitium\zmq;

/**
 * Publish messages to endpoint
 *
 * Class Publisher
 * @package limitium\zmq
 */
class Publisher
{
    /**
     * @var \ZMQContext
     */
    private $context;
    /**
     * @var \ZMQSocket
     */
    private $socket;
    /**
     * @var bool
     */
    private $verbose;

    public function __construct($verbose = false, \ZMQContext $context = null)
    {
        if (!$context) {
            $context = new \ZMQContext();
        }
        $this->context = $context;
        $this->verbose = $verbose;

        $this->socket = $this->context->getSocket(\ZMQ::SOCKET_PUB);
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_HWM, 1);
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
    }

    public function bind($endpoint)
    {
        $this->socket->bind($endpoint);
        if ($this->verbose) {
            printf("I: Publisher is active at %s %s", $endpoint, PHP_EOL);
        }

    }

    public function send($data)
    {
        $msg = new Zmsg($this->socket);
        $msg->push($data);
        $msg->wrap(sprintf("%.0f", microtime(1) * 1000));
        $msg->send(true);
    }

}
