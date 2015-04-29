<?php

require('./../../vendor/autoload.php');

use limitium\zmq\Worker;


(new Worker('tcp://127.0.0.1:5555', 2500, 5000, null, 1))
    ->setExecutor(function ($msg) {
        return $msg + $msg;
    })
    ->work();