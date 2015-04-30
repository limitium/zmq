<?php

require('./../../vendor/autoload.php');

use limitium\zmq\Concentrator;


(new Concentrator('tcp://127.0.0.1:5555', null, true))
    ->setReceiver(function ($msg) {
        echo $msg;
    })
    ->listen();