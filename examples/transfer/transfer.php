<?php

require('./../../vendor/autoload.php');

use limitium\zmq\FastBox;


(new FastBox('tcp://127.0.0.1:6666', 'tcp://127.0.0.1:5555', 100, 2500, null, true))
    ->transfer();