<?php

namespace limitium\zmq;


abstract class PollBroker extends BaseBroker
{
    /**
     * @var \ZMQPoll
     */
    protected $poll;
    private $isPolling = true;
    private $pollTimeOut;

    public function __construct($endpoint, $pollTimeOut = 1000, \ZMQContext $context = null, $verbose = false)
    {
        parent::__construct($endpoint, $context, $verbose);
        $this->pollTimeOut = $pollTimeOut;
        $this->poll = new \ZMQPoll();
    }

    /**
     * Start to poll for events
     * @throws \Exception
     */
    protected function poll()
    {
        if (!$this->poll) {
            throw new \Exception("Pool doesn't initialized");
        }
        $this->isPolling = true;
        $read = $write = [];
        while ($this->isPolling) {
            $events = $this->poll->poll($read, $write, $this->pollTimeOut);
            $this->onPoll($events, $read, $write);
        }
    }

    /**
     * Stops polling process
     * @return $this
     */
    public function stopPolling()
    {
        $this->isPolling = false;
        return $this;
    }

    abstract protected function onPoll($events, $read, $write);
}