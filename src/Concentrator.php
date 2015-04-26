<?php

namespace limitium\zmq;

/**
 * Collect messages from several publishers to callable
 *
 * Class Concentrator
 * @package limitium\zmq
 */
class Concentrator
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
    /**
     * @var \ZMQPoll
     */
    private $poll;

    /**
     * @param $endpoint Concentrator endpoint
     * @param bool $verbose
     */
    public function __construct($endpoint, $verbose = false)
    {
        $this->context = new \ZMQContext();
        $this->endpoint = $endpoint;
        $this->verbose = $verbose;

        $this->socket = $this->context->getSocket(\ZMQ::SOCKET_SUB);
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, "");

        $this->poll = new \ZMQPoll();
    }

    public function bind()
    {
        $this->socket->bind($this->endpoint);
        $this->poll->add($this->socket, \ZMQ::POLL_IN);
        if ($this->verbose) {
            printf("I: sub listener at %s... %s", $this->endpoint, PHP_EOL);
        }
    }

    public function listen()
    {
        $read = $write = array();
        while (true) {
            $events = $this->poll->poll($read, $write, 1000);
            $msg = array();
            if ($events > 0) {
                $zmsg = new Zmsg($this->socket);
                $zmsg->recv();
                if ($this->verbose) {
                    echo "I: received message from client:", PHP_EOL;
                    echo $zmsg->__toString(), PHP_EOL;
                }
                while ($part = $zmsg->pop()) {
                    $msg[] = $part;
                }
            }
            call_user_func($this->receiver, $msg);
        }
    }

    public function setReceiver(callable $receiver)
    {
        $this->receiver = $receiver;
    }
}
