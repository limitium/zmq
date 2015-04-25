<?php

namespace limitium\zmq;

class Log
{
    const ERROR = 1;
    const WARN = 2;
    const INFO = 3;
    const DEBUG = 4;
    /**
     * @var \ZMQContext
     */
    private $context;

    private $type;
    private $broker;
    private $socket;
    private $verbose;


    public function __construct($broker, $type, $verbose = false)
    {
        $this->context = new \ZMQContext();
        $this->broker = $broker;
        $this->type = $type;
        $this->identifier = md5(md5(microtime(1)) . rand(0, 1000));
        $this->verbose = $verbose;
        $this->connect();
    }

    private function connect()
    {
        $this->socket = $this->context->getSocket(\ZMQ::SOCKET_PUB);
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_HWM, 50);
        $this->socket->connect($this->broker);

        if ($this->verbose) {
            printf("I: connecting to subscriber at %s... %s", $this->broker, PHP_EOL);
        }
    }

    private function send($level, $data)
    {
        $msg = new Zmsg($this->socket);
        if (!is_array($data)) {
            $data = array($data);
        }
        $data = array_reverse($data);
        foreach ($data as $part) {
            $msg->push($part);
        }
        $msg->wrap($level);
        $msg->wrap(sprintf("%.0f", microtime(1) * 1000));
        $msg->wrap($this->type);
        $msg->wrap($this->identifier);
        if ($this->verbose) {
            print_r("I: send msg");
            echo PHP_EOL, $msg, PHP_EOL;
        }
        $msg->send(true);
    }

    public function error($data)
    {
        $this->send(Log::ERROR, $data);
    }

    public function warn($data)
    {
        $this->send(Log::WARN, $data);
    }

    public function info($data)
    {
        $this->send(Log::INFO, $data);
    }

    public function debug($data)
    {
        $this->send(Log::DEBUG, $data);
    }
}
