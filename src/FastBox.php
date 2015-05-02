<?php

namespace limitium\zmq;

/**
 * Collect messages from publishers and transfer them to workers
 *
 * Class FastBox
 * @package limitium\zmq
 */
class FastBox extends Ventilator
{
    private $queue;
    private $queueLimit;
    /**
     * @var \ZMQSocket
     */
    private $frontedSocket;

    /**
     * @param $publisherEndpoint
     * @param int $workersEndpoint
     * @param int $queueLimit
     * @param int $heartbeatDelay
     * @param \ZMQContext $context
     * @param bool $verbose
     * @internal param $endpoint
     * @internal param $collectEndpoint
     */
    public function __construct($publisherEndpoint, $workersEndpoint, $queueLimit = 100, $heartbeatDelay = 2500, \ZMQContext $context = null, $verbose)
    {
        parent::__construct($workersEndpoint, $heartbeatDelay, $context, $verbose);

        $this->queueLimit = $queueLimit;
        $this->queue = [];

        $this->connect($publisherEndpoint);

        $this->generator = function() {
            return array_shift($this->queue);
        };
    }

    private function connect($publisherEndpoint)
    {
        if ($this->frontedSocket) {
            $this->poll->remove($this->frontedSocket);
            unset($this->frontedSocket);
        }

        $this->frontedSocket = $this->createSocket(\ZMQ::SOCKET_SUB, [
            \ZMQ::SOCKOPT_LINGER => 0,
            \ZMQ::SOCKOPT_SUBSCRIBE => ""
        ], true);

        $this->frontedSocket->connect($publisherEndpoint);
        $this->poll->add($this->frontedSocket, \ZMQ::POLL_IN);

        if ($this->verbose) {
            printf("I: Connecting to publisher at %s... %s", $publisherEndpoint, PHP_EOL);
        }
    }

    /**
     * Transfer messages from publisher to workers
     *
     * @throws \Exception
     */
    public function transfer()
    {
        $this->poll();
    }

    protected function onPoll($events, $read, $write)
    {
        parent::onPoll($events, $read, $write);

        if ($events > 0) {
            foreach ($read as $socket) {
                //handle publisher
                if ($socket === $this->frontedSocket) {
                    $zmsg = new Zmsg($this->frontedSocket);
                    $zmsg->recv();
                    if ($this->verbose) {
                        echo "I: received message from publisher size: ";
                        echo strlen($zmsg->__toString()), PHP_EOL;
                    }
                    $zmsg->unwrap(); //time
                    if ($this->queueLimit > sizeof($this->queue)) {
                        array_unshift($this->queue, $zmsg->pop());
                    }
                }
            }
        }
    }
}