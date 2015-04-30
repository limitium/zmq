# zmq
Brokers for ZeroMQ from TA:map

[![Build Status](https://travis-ci.org/limitium/zmq.svg?branch=master)](https://travis-ci.org/limitium/zmq)
[![Dependency Badge](https://www.versioneye.com/user/projects/55437151d8fe1a25cc00008b/badge.svg?style=flat)](https://www.versioneye.com/user/projects/55437151d8fe1a25cc00008b)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/limitium/zmq/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/limitium/zmq/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/limitium/zmq/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limitium/zmq/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/limitium/zmq/v/stable)](https://packagist.org/packages/limitium/zmq)
[![Total Downloads](https://poser.pugx.org/limitium/zmq/downloads)](https://packagist.org/packages/limitium/zmq)
[![Latest Unstable Version](https://poser.pugx.org/limitium/zmq/v/unstable)](https://packagist.org/packages/limitium/zmq)
[![License](https://poser.pugx.org/limitium/zmq/license)](https://packagist.org/packages/limitium/zmq)
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
