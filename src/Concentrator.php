<?php

namespace limitium\zmq;

/**
 * Collect messages from several publishers to callable
 *
 * Class Concentrator
 * @package limitium\zmq
 */
class Concentrator extends BaseBroker
{
    private $isListen;
    /**
     * @var \ZMQPoll
     */
    private $poll;

    /**
     * @param $endpoint
     * @param \ZMQContext $context
     * @param bool $verbose
     */
    public function __construct($endpoint, \ZMQContext $context = null, $verbose = false)
    {
        parent::__construct($endpoint, $context, $verbose);

        $this->createSocket(\ZMQ::SOCKET_SUB, [
            \ZMQ::SOCKOPT_LINGER => 0,
            \ZMQ::SOCKOPT_SUBSCRIBE => ""
        ]);

        $this->poll = new \ZMQPoll();

        $this->bind();
    }

    private function bind()
    {
        $this->socket->bind($this->endpoint);
        $this->poll->add($this->socket, \ZMQ::POLL_IN);
        if ($this->verbose) {
            printf("I: concentrator listener at %s... %s", $this->endpoint, PHP_EOL);
        }
    }

    /**
     * Start listen for messages in loop
     *
     * @throws Exception
     */
    public function listen()
    {
        $this->isListen = true;
        $read = $write = [];
        while ($this->isListen) {
            $events = $this->poll->poll($read, $write, 1000);
            if ($events > 0) {
                $msg = [];
                $zmsg = new Zmsg($this->socket);
                $zmsg->recv();
                if ($this->verbose) {
                    echo "I: received message from client:", PHP_EOL;
                    echo $zmsg->__toString(), PHP_EOL;
                }
                while ($part = $zmsg->pop()) {
                    $msg[] = $part;
                }
                call_user_func($this->receiver, $msg);
            }
        }
    }

    /**
     * Called on every received message
     *
     * @param callable $receiver with params $msg, $sendTime
     * @return $this
     */
    public function setReceiver(callable $receiver)
    {
        $this->receiver = $receiver;
        return $this;
    }

    /**
     * Stops to listen for messages
     *
     * @return $this
     */
    public function stop()
    {
        $this->isListen = false;
        return $this;
    }
}
