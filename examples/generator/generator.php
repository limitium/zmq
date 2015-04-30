<?php

require('./../../vendor/autoload.php');

use limitium\zmq\Ventilator;


(new Ventilator('tcp://127.0.0.1:5555', 2500, null, true))
    ->setGenerator(function () {
        sleep(1);
        return rand();
    })
    ->setResponder(function ($msg) {
        echo $msg;
    })
    ->listen();
