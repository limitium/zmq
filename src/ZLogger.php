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
class ZLogger extends BaseBroker implements LoggerInterface
{
    use LoggerTrait;

    const CONTEXT_DELIMITER = "__ctx";
    /**
     * @var Service name in logs
     */
    private $logName;

    /**
     * @param $logName
     * @param $endpoint
     * @param \ZMQContext $context
     * @param bool $verbose
     */
    public function __construct($logName, $endpoint, \ZMQContext $context = null, $verbose = false)
    {
        parent::__construct($endpoint, $context, $verbose);
        $this->logName = $logName;
        $this->connect();
    }

    private function connect()
    {
        $this->createSocket(\ZMQ::SOCKET_PUB, [
            \ZMQ::SOCKOPT_HWM => 0
        ]);
        $this->socket->connect($this->endpoint);

        if ($this->verbose) {
            printf("I: connecting to subscriber at %s... %s", $this->endpoint, PHP_EOL);
        }
    }

    /**
     * @param $level
     * @param $message
     * @param array $context
     * @throws \Exception
     */
    private function send($level, $message, array $context)
    {
        $msg = new Zmsg($this->socket);
        if (sizeof($context) > 0) {
            $msg->wrap(json_encode($context));
            $msg->wrap(ZLogger::CONTEXT_DELIMITER);
        }
        $msg->wrap($message);
        $msg->wrap($level);
        $msg->wrap(sprintf("%.0f", microtime(1) * 1000));
        $msg->wrap($this->logName);
        if ($this->verbose) {
            print_r("I: send msg");
            echo PHP_EOL, $msg, PHP_EOL;
        }
        $msg->send(true);
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
