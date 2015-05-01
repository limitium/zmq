ZMQ
================

Brokers for [ZeroMQ](http://zeromq.org/)from [TA:map](https://github.com/limitium/C-CTA-map-frontend/) project 

[![Build Status](https://travis-ci.org/limitium/zmq.svg?branch=master)](https://travis-ci.org/limitium/zmq)
[![Dependency Badge](https://www.versioneye.com/user/projects/55437151d8fe1a25cc00008b/badge.svg?style=flat)](https://www.versioneye.com/user/projects/55437151d8fe1a25cc00008b)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/limitium/zmq/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/limitium/zmq/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/limitium/zmq/v/stable)](https://packagist.org/packages/limitium/zmq)
[![Total Downloads](https://poser.pugx.org/limitium/zmq/downloads)](https://packagist.org/packages/limitium/zmq)
[![Latest Unstable Version](https://poser.pugx.org/limitium/zmq/v/unstable)](https://packagist.org/packages/limitium/zmq)
[![License](https://poser.pugx.org/limitium/zmq/license)](https://packagist.org/packages/limitium/zmq)

## Install(linux)

#### 1. Install ZeroMQ

```bash
    sudo apt-get update -qq
    sudo apt-get install -y libzmq3-dev
```

#### 2. Install php-zmq binding

```bash
    git clone https://github.com/mkoppanen/php-zmq.git
    sh -c "cd php-zmq && phpize && ./configure && make --silent && sudo make install"
    echo "extension=zmq.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
```

### 3. Require ZMQ via Composer

```bash
    composer require limitium/zmq
```

## Tests

```bash
    phpunit
```

## Usage

### PSR-3 distributed logger

Can be used in single process, several process on single machine or on several machines. 

#### logger

```php
    $logger = new ZLogger('my_service_1', 'tcp://127.0.0.1:5555');
    $logger->info("core is stable");
    $logger->emergency("we're all going to die!");
```

#### collector

```php
    (new Concentrator('tcp://127.0.0.1:5555'))
        ->setReceiver(function ($logMsg) {
            $serviceName = $logMsg[0];
            $time = $logMsg[1];
            $logLevel = $logMsg[2];
            $logMsg = $logMsg[3];
        })
        ->listen();
```

### Task generator

With workers management (checks workers statuses, checks workers heartbeats etc)

#### Generator

```php
    (new Ventilator('tcp://127.0.0.1:5555'))
        ->setGenerator(function () {
            sleep(1);
            return rand();
        })
        ->setResponder(function ($msg) {
            echo $msg;
        })
        ->listen();
```

#### Worker

```php
    (new Worker('tcp://127.0.0.1:5555'))
        ->setExecutor(function ($msg) {
            return $msg + $msg;
        })
        ->work();
```