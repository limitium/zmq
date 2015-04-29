<?php

namespace limitium\zmq\test;

use limitium\zmq\Concentrator;
use limitium\zmq\Zmsg;
use PHPUnit_Framework_TestCase;

class ConcentratorTest extends PHPUnit_Framework_TestCase
{
    public function testConcentrate()
    {
        $context = new \ZMQContext();

        $endpoint = "inproc://zmq_concentrator";

        $concentrator = new Concentrator($endpoint, $context);

        $pub1 = $this->createPublisher($context, $endpoint);
        $pub2 = $this->createPublisher($context, $endpoint);

        $concentrator->emptyPoll();

        $msgs[] = "qweqwe";
        $pub1->push($msgs[0]);
        $pub1->send(true);

        $msgs[] = "asdzxc";
        $pub2->push($msgs[1]);
        $pub2->send(true);

        $msgs[] = "qweqwe2";
        $pub1->push($msgs[2]);
        $pub1->send(true);


        $msgLength = sizeof($msgs);
        $concentrator->setReceiver(function ($msg) use ($concentrator, &$msgLength, $msgs) {
            $this->assertEquals(array_reverse($msgs)[--$msgLength], $msg);
            if (!$msgLength) {
                $concentrator->stop();
            }
        })->listen();
    }

    /**
     * @param $context
     * @param $endpoint
     * @return Zmsg
     */
    private function createPublisher($context, $endpoint)
    {
        $publisher = new \ZMQSocket($context, \ZMQ::SOCKET_PUB);
        $publisher->setSockOpt(\ZMQ::SOCKOPT_SNDHWM, 1);
        $publisher->connect($endpoint);
        return new Zmsg($publisher);
    }
}