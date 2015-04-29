<?php

namespace limitium\zmq;

/**
 * Collect messages from several publishers to callable
 *
 * Class Concentrator
 * @package limitium\zmq
 */
class Concentrator extends PollBroker
{
    /**
     * @var callable
     */
    private $receiver;

    /**
     * @param $endpoint
     * @param \ZMQContext $context
     * @param bool $verbose
     */
    public function __construct($endpoint, \ZMQContext $context = null, $verbose = false)
    {
        parent::__construct($endpoint, 1000, $context, $verbose);

        $this->createSocket(\ZMQ::SOCKET_PULL,[
//            \ZMQ::SOCKOPT_SUBSCRIBE => ""
        ]);

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
        if (!$this->receiver) {
            throw new \Exception("Empty receiver");
        }
        $this->poll();
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
        return $this->stopPolling();
    }

    protected function onPoll()
    {
    }

    protected function onPollEvents($read, $write)
    {
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
        call_user_func($this->receiver, sizeof($msg) == 1 ? $msg[0] : $msg);
    }
}
