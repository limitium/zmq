<?php

namespace limitium\zmq;

/**
 * Continuously sends non empty messages to free workers
 *
 * Class Ventilator
 * @package limitium\zmq
 */
class Ventilator extends PollBroker
{
    // Heartbeat management
    protected $heartbeatAt; // When to send HEARTBEAT
    protected $heartbeatDelay; // Heartbeat delay, msecs
    protected $heartbeatMaxFails = 4;

    //workers
    protected $workers;
    protected $workersFree;

    protected $generator;
    protected $responder;

    public function __construct($endpoint, $heartbeatDelay = 2500, \ZMQContext $context = null, $verbose)
    {
        parent::__construct($endpoint, $heartbeatDelay, $context, $verbose);

        $this->createSocket(\ZMQ::SOCKET_XREP, [
            \ZMQ::SOCKOPT_LINGER => 0
        ]);

        $this->heartbeatDelay = $heartbeatDelay;
        $this->workers = array();
        $this->workersFree = array();

        $this->bind($endpoint);
    }

    /**
     * Sets tasks generator
     * @param callable $generator
     * @return $this
     */
    public function setGenerator(callable $generator)
    {
        $this->generator = $generator;
        return $this;
    }

    /**
     * Sets responder handler
     * @param callable $responder
     * @return $this
     */
    public function setResponder(callable $responder)
    {
        $this->responder = $responder;
        return $this;
    }

    /**
     * Start sends tasks to workers
     * @throws \Exception
     */
    public function listen()
    {
        if (!$this->generator) {
            throw new \Exception("Empty generator");
        }

        $this->poll();
    }

    protected function bind($endpoint)
    {
        $this->socket->bind($endpoint);
        $this->poll->add($this->socket, \ZMQ::POLL_IN);
        if ($this->verbose) {
            printf("I: Generator is active at %s %s", $endpoint, PHP_EOL);
        }
    }


    protected function onPoll($events, $read, $write)
    {
        if ($events) {
            foreach ($read as $socket) {
                if ($socket === $this->socket) {
                    $zmsg = new Zmsg($this->socket);
                    $zmsg->recv();
                    if ($this->verbose) {
                        echo "I: received message:", PHP_EOL, $zmsg->__toString(), PHP_EOL;
                    }

                    $sender = $zmsg->pop();
                    $zmsg->pop(); //empty
                    $header = $zmsg->pop();
                    if ($header == Commands::W_WORKER) {
                        $this->process($sender, $zmsg);
                    } else {
                        echo "E: invalid header `$header` in  message", PHP_EOL, $zmsg->__toString(), PHP_EOL, PHP_EOL;
                    }
                }
            }
        }

        $this->generateTasks();
        $this->sendHeartbeats();
    }

    protected function sendHeartbeats()
    {
        if (microtime(true) > $this->heartbeatAt) {
            if ($this->verbose) {
                echo "I: send heartbeats to " . sizeof($this->workersFree) . " workers", PHP_EOL;
            }
            foreach ($this->workersFree as $worker) {
                $this->workerSend($worker, Commands::W_HEARTBEAT);
            }
            $this->heartbeatAt = microtime(true) + ($this->heartbeatDelay / 1000);
        }
    }

    protected function process($sender, Zmsg $zmsg)
    {
        $command = $zmsg->pop();
        $hasWorker = $this->hasWorker($sender);

        switch ($command) {
            case Commands::W_READY:
                if (!$hasWorker) {
                    $this->addWorker($sender);
                } else {
                    echo "E: Ready from ready worker `$sender` - disconnect ", PHP_EOL;
                    $this->deleteWorker($this->workers[$sender], true);
                }
                break;
            case Commands::W_HEARTBEAT:
                if ($hasWorker) {
                    $this->live($this->workers[$sender]);
                } else {
                    echo "E: Heartbeat from not ready worker `$sender` - disconnect ", PHP_EOL;
                    $this->send($sender, Commands::W_RESPONSE);
                }
                break;
            case Commands::W_RESPONSE:
                if ($hasWorker) {
                    if ($this->responder) {
                        $response = $zmsg->pop();
                        call_user_func($this->responder, $response);
                    }
                    $this->free($this->workers[$sender]);
                } else {
                    echo "E: Response from not ready worker `$sender` - disconnect ", PHP_EOL;
                    $this->send($sender, Commands::W_RESPONSE);
                }
                break;
            default:
                echo "E: Unsupported command `$command`.", PHP_EOL;
                echo $zmsg->__toString(), PHP_EOL, PHP_EOL;
        }
    }

    protected function addWorker($address)
    {
        if ($this->verbose) {
            echo "I: add new worker:", PHP_EOL;
        }
        $worker = new WorkerAddress($address);
        $this->workers[$address] = $worker;
        $this->free($worker);
        return $worker;
    }

    protected function free($worker)
    {
        $this->workersFree[] = $worker;
        $this->live($worker);
        $this->generateTasks();
    }

    protected function live(WorkerAddress $worker)
    {
        $worker->aliveFor($this->heartbeatMaxFails * $this->heartbeatDelay);
    }

    protected function hasWorker($address)
    {
        return isset($this->workers[$address]);
    }

    protected function workerSend(WorkerAddress $worker, $command, $data = null)
    {
        $zmsg = null;
        if ($data) {
            $zmsg = new Zmsg();
            $zmsg->body_set($data);
        }
        $this->send($worker->address, $command, $zmsg);
    }

    protected function send($address, $command, $zmsg = null)
    {
        $zmsg = $zmsg ? $zmsg : new Zmsg();
        $zmsg->push($command);
        $zmsg->push(Commands::W_WORKER);
        $zmsg->wrap($address, "");
        if ($this->verbose) {
            printf("I: sending `%s` to worker %s", $command, PHP_EOL);
            echo $zmsg->__toString(), PHP_EOL, PHP_EOL;
        }
        $zmsg->set_socket($this->socket)->send();
    }

    protected function purgeWorkers()
    {
        foreach ($this->workersFree as $worker) {
            if ($worker->expiry < microtime(1)) {
                echo "I: expired worker `$worker->address`", PHP_EOL;
                $this->deleteWorker($worker);
            }
        }
    }

    protected function deleteWorker(WorkerAddress $worker, $disconnect = false)
    {
        if ($this->verbose) {
            echo "I: remove worker `$worker->address` " . ($disconnect ? "disconnect" : ""), PHP_EOL;
        }
        if ($disconnect) {
            $this->workerSend($worker, Commands::W_RESPONSE);
        }
        unset($this->workers[$worker->address]);
        $index = array_search($worker, $this->workersFree);
        if ($index !== false) {
            unset($this->workersFree[$index]);
        }
    }

    protected function generateTasks()
    {
        $this->purgeWorkers();
        foreach ($this->workersFree as $k => $worker) {
            $task = call_user_func($this->generator);
            if ($task) {
                $this->workerSend($worker, Commands::W_REQUEST, $task);
                unset($this->workersFree[$k]);
            }
        }
    }

}
