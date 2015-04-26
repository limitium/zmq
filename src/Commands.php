<?php
namespace limitium\zmq;
/**
 * Class Commands
 * @package limitium\zmq
 */
class Commands
{
    const W_WORKER = "w";
    const W_READY = "wr";
    const W_REQUEST = "wrq";
    const W_HEARTBEAT = "whb";
    const W_RESPONSE = "wrs";
    const W_DISCONNECT = "wrd";
}
