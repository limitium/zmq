<?php

namespace limitium\zmq;

/**
 * Receive messages from publisher
 *
 * Class Subscriber
 * @package limitium\zmq
 */
class Subscriber
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

    private $normalDelay = false;
    private $maxAllowedDelay;
    private $listner;
    private $misser;

    public function __construct($publisherEndpoint, $maxAllowedDelay = 100, $verbose = false)
    {
        $this->context = new \ZMQContext();
        $this->verbose = $verbose;
        $this->connect($publisherEndpoint);
    }

    private function connect($publisherEndpoint)
    {

        $this->socket = $this->context->getSocket(\ZMQ::SOCKET_SUB);
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, "");
        $this->socket->connect($publisherEndpoint);

        if ($this->verbose) {
            printf("I: connecting to publisher at %s... %s", $this->broker, PHP_EOL);
        }
    }

    public function listen()
    {
        while (true) {
            $zmsg = new Zmsg($this->socket);
            $zmsg->recv();
            if ($this->verbose) {
                echo "I: received message from broker:", PHP_EOL;
                echo $zmsg->__toString(), PHP_EOL;
            }
            $time = $zmsg->unwrap();
            if (!$this->normalDelay) {
                $this->normalDelay = microtime(true) * 1000 - $time;
            }

            $delayTime = microtime(true) * 1000 - $time;
            if ($delayTime > $this->normalDelay + $this->maxAllowedDelay) {
                call_user_func($this->misser, $delayTime);
            }
            call_user_func($this->listner, $zmsg->pop(), $time);
            $this->sequence = $time;
        }
    }

    public function setListener(callable $listener)
    {
        $this->listner = $listener;
    }

    public function setMisser(callable $misser)
    {
        $this->misser = $misser;
    }
}

