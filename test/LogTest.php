<?php

namespace limitium\zmq\test;

use limitium\zmq\ZLogger;
use limitium\zmq\Zmsg;
use PHPUnit_Framework_TestCase;

class LogTest extends PHPUnit_Framework_TestCase
{
    private static $context;

    public static function setUpBeforeClass()
    {
        self::$context = new \ZMQContext();
    }

    public function testTwoLoggerWithSimpleMsg()
    {
        $endpoint = "inproc://zmq_logger1";

        $receiver = $this->createCollector(self::$context, $endpoint);

        $log1 = new ZLogger('l1', $endpoint, self::$context);
        $log2 = new ZLogger('l2', $endpoint, self::$context);

        $this->emptyPoll($receiver);
        $collector = new Zmsg($receiver);

        $msgOut = "ololo";
        $log1->emergency($msgOut);
        $collector->recv();

        $this->assertEquals($collector->parts(), 4);
        $this->assertEquals($collector->body(), $msgOut);
        $this->assertEquals($collector->pop(), 'l1');
        $collector->pop(); //time
        $this->assertEquals($collector->pop(), 'emergency');

        $msgOk = "ok";
        $log2->info($msgOk);
        $collector->recv();

        $this->assertEquals($collector->parts(), 4);
        $this->assertEquals($collector->body(), $msgOk);
        $this->assertEquals($collector->pop(), 'l2');
        $collector->pop(); //time
        $this->assertEquals($collector->pop(), 'info');

    }

    public function testLoggerWithContext()
    {
        $endpoint = "inproc://zmq_logger2";
        $receiver = $this->createCollector(self::$context, $endpoint);

        $log1 = new ZLogger('l3', $endpoint, self::$context);

        $this->emptyPoll($receiver);
        $collector = new Zmsg($receiver);

        $msgOut = "asd123";
        $context = [
            1 => 2,
            'a' => 'b'
        ];
        $log1->debug($msgOut, $context);

        $collector->recv();

        $this->assertEquals($collector->parts(), 6);
        $this->assertEquals($collector->body(), json_encode($context));

        $this->assertEquals($collector->pop(), 'l3');
        $collector->pop(); //time
        $this->assertEquals($collector->pop(), 'debug');
        $this->assertEquals($collector->pop(), $msgOut);
        $this->assertEquals($collector->pop(), ZLogger::CONTEXT_DELIMITER);
        $this->assertEquals($collector->pop(), json_encode($context));
    }


    /**
     * @param $context
     * @param $endpoint
     * @return Zmsg
     */
    private function createCollector($context, $endpoint)
    {
        $receiver = new \ZMQSocket($context, \ZMQ::SOCKET_SUB);
        $receiver->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $receiver->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, "");
        $receiver->bind($endpoint);
        return $receiver;
    }

    /**
     * @param $receiver
     */
    public function emptyPoll($receiver)
    {
        $poll = new \ZMQPoll();
        $poll->add($receiver, \ZMQ::POLL_IN);
        $readable = $writable = array();
        $poll->poll($readable, $writable, 0); // Timeout immediately.
    }
}