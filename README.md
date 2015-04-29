# zmq
Brokers for ZeroMQ

[![Build Status](https://travis-ci.org/limitium/zmq.svg?branch=master)](https://travis-ci.org/limitium/zmq)

===

``server.php``

    <?php
    require_once "Ventilator.php";

    $server = new Ventilator(true);
    $server->bind("tcp://*:5555");

    $server->setGenerator(function () {
        return mt_rand(1, 1000);
    });

    $server->setResponder(function ($data) {
      print_r("Got data:$data" . PHP_EOL);
    });

    $server->listen();

``worker.php``

    <?php
    require_once "Worker.php";

    $wrk = new Worker("tcp://localhost:5555", true);

    $wrk->setExecuter(function ($data) {
        sleep(5);
        return "done $data";
    });

    $wrk->work();
