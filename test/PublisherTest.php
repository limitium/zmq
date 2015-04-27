<?php

namespace limitium\zmq\test;

use limitium\zmq\Publisher;
use limitium\zmq\Zmsg;
use PHPUnit_Framework_TestCase;

class PublisherTest extends PHPUnit_Framework_TestCase
{
    public function testSend()
    {
        $context = new \ZMQContext();

        $endpoint = "inproc://zmq_publisher";
        $publisher = new Publisher($endpoint, $context);

        $receiver = new \ZMQSocket($context, \ZMQ::SOCKET_SUB);
        $receiver->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $receiver->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, "");
        $receiver->connect($endpoint);
        $zmsgi = new Zmsg($receiver);

        $msgOut = "azaza";
        $publisher->send($msgOut);
        $zmsgi->recv();

        $this->assertTrue($zmsgi->body() == $msgOut);
    }
}