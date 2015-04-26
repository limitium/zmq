<?php

namespace limitium\zmq;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * PSR-3 log message publisher
 *
 * Class Log
 * @package limitium\zmq
 */
class Log implements LoggerInterface
{
    use LoggerTrait;

    const CONTEXT_DELIMITER = "cont";
    /**
     * @var \ZMQContext
     */
    private $context;
    /**
     * @var Service name in logs
     */
    private $logName;
    /**
     * @var Endpoint of log concentrator
     */
    private $logEndPoint;
    /**
     * @var \ZMQSocket
     */
    private $socket;
    private $verbose;

    /**
     * @param $logEndPoint Endpoint of log concentrator
     * @param $logName Service name in logs
     * @param bool $verbose
     */
    public function __construct($logEndPoint, $logName, $verbose = false)
    {
        $this->context = new \ZMQContext();
        $this->logEndPoint = $logEndPoint;
        $this->logName = $logName;
        $this->identifier = md5(md5(microtime(1)) . rand(0, 1000));
        $this->verbose = $verbose;
        $this->connect();
    }

    private function connect()
    {
        $this->socket = $this->context->getSocket(\ZMQ::SOCKET_PUB);
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $this->socket->setSockOpt(\ZMQ::SOCKOPT_HWM, 50);
        $this->socket->connect($this->logEndPoint);

        if ($this->verbose) {
            printf("I: connecting to subscriber at %s... %s", $this->logEndPoint, PHP_EOL);
        }
    }

    /**
     * @param $level
     * @param $data
     * @param array $context
     * @throws Exception
     */
    private function send($level, $data, array $context)
    {
        $msg = new Zmsg($this->socket);
        if (!is_array($data)) {
            $data = array($data);
        }
        if (sizeof($context) > 0) {
            $this->sendArray($context, $msg);
            $msg->wrap(Log::CONTEXT_DELIMITER);
        }
        $this->sendArray($data, $msg);
        $msg->wrap($level);
        $msg->wrap(sprintf("%.0f", microtime(1) * 1000));
        $msg->wrap($this->logName);
        $msg->wrap($this->identifier);
        if ($this->verbose) {
            print_r("I: send msg");
            echo PHP_EOL, $msg, PHP_EOL;
        }
        $msg->send(true);
    }

    /**
     * @param array $data
     * @param Zmsg $msg
     */
    private function sendArray(array $data, Zmsg $msg)
    {
        $data = array_reverse($data);
        foreach ($data as $part) {
            $msg->push($part);
        }
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        $this->send($level, $message, $context);
    }
}
