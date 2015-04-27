<?php

namespace limitium\zmq\test;

use limitium\zmq\Zmsg;
use PHPUnit_Framework_TestCase;

class ZmsgTest extends PHPUnit_Framework_TestCase
{
    private static $outputSocket;
    private static $inputSocket;

    public static function setUpBeforeClass()
    {
        $context = new \ZMQContext();
        self::$outputSocket = new \ZMQSocket($context, \ZMQ::SOCKET_DEALER);
        self::$outputSocket->bind("inproc://zmq_zmsg");

        self::$inputSocket = new \ZMQSocket($context, \ZMQ::SOCKET_ROUTER);
        self::$inputSocket->connect("inproc://zmq_zmsg");
    }

    public function testSinglePartMessage()
    {
        $zmsgo = new Zmsg(self::$outputSocket);
        $zmsgo->body_set("Hello");
        $this->assertTrue($zmsgo->body() == "Hello");
        $zmsgo->send();

        $zmsgi = new Zmsg(self::$inputSocket);
        $zmsgi->recv();
        $this->assertTrue($zmsgi->parts() == 2);
        $this->assertTrue($zmsgi->body() == "Hello");
    }

    public function testMultiPartMessage()
    {
        $zmsgo = new Zmsg(self::$outputSocket);
        $zmsgo->body_set("Hello");
        $zmsgo->wrap("address1", "");
        $zmsgo->wrap("address2");
        $this->assertTrue($zmsgo->parts() == 4);
        $zmsgo->send();

        $zmsgi = new Zmsg(self::$inputSocket);
        $zmsgi->recv();
        $this->assertTrue($zmsgi->parts() == 5);
        $zmsgi->unwrap();
        $this->assertTrue($zmsgi->unwrap() == "address2");

        $zmsgi->body_fmt("%s%s", 'W', "orld");
        $this->assertTrue($zmsgi->body() == "World");

// Pull off address 1, check that empty part was dropped
        $zmsgi->unwrap();
        $this->assertTrue($zmsgi->parts() == 1);

// Check that message body was correctly modified
        $part = $zmsgi->pop();
        $this->assertTrue($part == "World");
        $this->assertTrue($zmsgi->parts() == 0);
    }

    public function testSaveLoad()
    {
        $zmsg = new Zmsg();
        $zmsg->body_set("Hello");
        $zmsg->wrap("address1", "");
        $zmsg->wrap("address2");
        $this->assertTrue($zmsg->parts() == 4);
        $fh = fopen(sys_get_temp_dir() . "/zmsgtest.zmsg", 'w');
        $zmsg->save($fh);
        fclose($fh);
        $fh = fopen(sys_get_temp_dir() . "/zmsgtest.zmsg", 'r');
        $zmsg2 = new Zmsg();
        $zmsg2->load($fh);
        $this->assertTrue($zmsg2->last() == $zmsg->last());
        fclose($fh);
        $this->assertTrue($zmsg2->parts() == 4);
    }
}