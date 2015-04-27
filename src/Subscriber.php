<?php

namespace limitium\zmq;

/**
 * Receive messages from publisher
 *
 * Class Subscriber
 * @package limitium\zmq
 */
class Subscriber extends BaseBroker
{

    private $normalDelay = false;
    private $isListen = true;
    private $maxAllowedDelay;
    private $listner;
    private $misser;

    public function __construct($publisherEndpoint, \ZMQContext $context = null, $maxAllowedDelay = 100, $verbose = false)
    {
        parent::__construct($publisherEndpoint, $context, $verbose);

        $this->maxAllowedDelay = $maxAllowedDelay;

        $this->connect();
    }

    private function connect()
    {
        $this->createSocket(\ZMQ::SOCKET_SUB,
            [
                \ZMQ::SOCKOPT_LINGER => 0,
                \ZMQ::SOCKOPT_SUBSCRIBE => ""
            ]);

        $this->socket->connect($this->endpoint);

        if ($this->verbose) {
            printf("I: connecting to publisher at %s... %s", $this->endpoint, PHP_EOL);
        }
    }

    /**
     * Start listen for messages in loop
     */
    public function listen()
    {
        while ($this->isListen) {
            $zmsg = new Zmsg($this->socket);
            $zmsg->recv();
            if ($this->verbose) {
                echo "I: received message from broker:", PHP_EOL;
                echo $zmsg->__toString(), PHP_EOL;
            }
            $time = $zmsg->unwrap();
            if (!$this->normalDelay) {
                $this->normalDelay = microtime(true) * 1000 - (double)$time;
            }

            $delayTime = microtime(true) * 1000 - (double)$time;
            if ($this->misser && $delayTime > $this->normalDelay + $this->maxAllowedDelay) {
                call_user_func($this->misser, $zmsg->pop(), $time, $delayTime);
            }
            call_user_func($this->listner, $zmsg->pop(), $time);
        }
    }

    /**
     * Called on every received message
     *
     * @param callable $listener with params $msg, $sendTime
     * @return $this
     */
    public function setListener(callable $listener)
    {
        $this->listner = $listener;
        return $this;
    }

    /**
     * Called if message sendTime > receiveTime + maxAllowedDelay
     *
     * @param callable $misser with params $msg, $sendTime, $delayTime
     * @return $this
     */
    public function setMisser(callable $misser)
    {
        $this->misser = $misser;
        return $this;
    }

    /**
     * Stops to listen for messages
     */
    public function stop()
    {
        $this->isListen = false;
    }

}

