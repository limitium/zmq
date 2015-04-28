<?php

namespace limitium\zmq\test;

use limitium\zmq\Log;
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
        $log1 = new Log('l1', $endpoint, self::$context);
        $log2 = new Log('l2', $endpoint, self::$context);
        $collector = $this->createCollector(self::$context, $endpoint);


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
        $log1 = new Log('l3', $endpoint, self::$context);
        $collector = $this->createCollector(self::$context, $endpoint);


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
        $this->assertEquals($collector->pop(), Log::CONTEXT_DELIMITER);
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
        sleep(1);
        return new Zmsg($receiver);
    }
}