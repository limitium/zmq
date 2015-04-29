<?php

require('./../../vendor/autoload.php');

use limitium\zmq\Ventilator;


(new Ventilator('tcp://127.0.0.1:5555', 2500, null, 1))
    ->setGenerator(function () {
        sleep(1);
        return rand();
    })
    ->setResponder(function ($msg) {
        var_dump($msg);
    })
    ->listen();
