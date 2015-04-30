<?php

require('./../../vendor/autoload.php');

use limitium\zmq\Publisher;


$publisher = new Publisher('tcp://127.0.0.1:6666', null, true);
while (true) {
    $publisher->send(rand());
    sleep(1);
}
