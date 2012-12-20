<?php

require_once "Zmsg.php";

class Concentrator
{
    private $context;
    private $socket;
    private $verbose;


    public function __construct($broker, $verbose = false)
    {
        $this->context = new ZMQContext();
        $this->broker = $broker;
        $this->verbose = $verbose;

        $this->socket = $this->context->getSocket(ZMQ::SOCKET_SUB);
        $this->socket->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
        $this->socket->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, "");
    }

    public function bind()
    {
        $this->socket->bind($this->broker);
        if ($this->verbose) {
            printf("I: sub listener at %s... %s", $this->broker, PHP_EOL);
        }
    }

    public function listen()
    {
        while (true) {
            $zmsg = new Zmsg($this->socket);
            $zmsg->recv();
            if ($this->verbose) {
                echo "I: received message from client:", PHP_EOL;
                echo $zmsg->__toString(), PHP_EOL;
            }
            $msg = array();
            while ($part = $zmsg->pop()) {
                $msg[] = $part;
            }
            call_user_func($this->receiver, $msg);
        }
    }

    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;
    }
}
