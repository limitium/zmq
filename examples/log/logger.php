<?php

require('./../../vendor/autoload.php');

use limitium\zmq\Log;
use Psr\Log\LogLevel;


$levels = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG];

$log = new Log('log_' . rand(), 'tcp://127.0.0.1:5555', null, true);

while (1) {
    $level = $levels[array_rand($levels)];

    $log->log($level, "Example of $level log");

    sleep(1);
}