<?php

namespace limitium\zmq;

/**
 * Publish messages to endpoint
 *
 * Class Publisher
 * @package limitium\zmq
 */
class Publisher extends BaseBroker
{

    public function __construct($endpoint, \ZMQContext $context = null, $verbose = false)
    {
        parent::__construct($endpoint, $context, $verbose);

        $this->createSocket(\ZMQ::SOCKET_PUB,
            [
                \ZMQ::SOCKOPT_SNDHWM => 1,
                \ZMQ::SOCKOPT_LINGER => 0
            ]);

        $this->bind();
    }

    private function bind()
    {
        $this->socket->bind($this->endpoint);
        if ($this->verbose) {
            printf("I: Publisher is active at %s %s", $this->endpoint, PHP_EOL);
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
