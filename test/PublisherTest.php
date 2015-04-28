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

        $sub1 = $this->createSubscriber($context, $endpoint);
        $sub2 = $this->createSubscriber($context, $endpoint);

        $msgOut = "azaza";
        $publisher->send($msgOut);

        $sub1->recv();
        $sub2->recv();

        $this->assertEquals($sub1->parts(), 2);
        $this->assertEquals($sub1->body(), $msgOut);
        $this->assertEquals($sub2->parts(), 2);
        $this->assertEquals($sub2->body(), $msgOut);
    }

    /**
     * @param $context
     * @param $endpoint
     * @return Zmsg
     */
    private function createSubscriber($context, $endpoint)
    {
        $receiver = new \ZMQSocket($context, \ZMQ::SOCKET_SUB);
        $receiver->setSockOpt(\ZMQ::SOCKOPT_LINGER, 0);
        $receiver->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, "");
        $receiver->connect($endpoint);
        return new Zmsg($receiver);
    }
}