<?php

namespace LucasVscn\Snowflake;

use ZMQContext, ZMQSocket, ZMQ;

/**
 * ZeroMQ Snowflake client
 *
 *
 * @package LucasVscn\Snowflake
 */
class Client
{
    protected $socket;

    /**
     * Constructor
     *
     * @param string $host
     * @param integer $port
     */
    public function __construct($host = 'localhost', $port = 5599)
    {
        $context = new ZMQContext();
        $this->socket  = new ZMQSocket($context, ZMQ::SOCKET_REQ);

        $this->socket->connect("tcp://{$host}:{$port}");

        $this->socket->setSockOpt(ZMQ::SOCKOPT_LINGER, 0);
    }

    /**
     * Asks the server for a new ID
     *
     * @return biginteger
     */
    public function nextId()
    {
        $this->socket->send('NEXT');
        return $this->socket->recv();
    }
}