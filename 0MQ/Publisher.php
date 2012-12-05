<?php
require_once "Zmsg.php";

class Publisher
{
    private $context;
    private $socket;
    private $verbose;

    public function __construct($verbose = false, $context = null)
    {
        if (!$context) {
            $context = new ZMQContext();
        }
        $this->context = $context;
        $this->verbose = $verbose;

        $this->socket = $this->context->getSocket(ZMQ::SOCKET_PUB);
        $this->socket->setSockOpt(ZMQ::SOCKOPT_HWM, 1);
        $this->socket->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
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
        $msg->wrap(microtime(true) * 1000);
        $msg->send(true);
    }

}
