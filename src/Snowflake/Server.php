<?php

namespace LucasVscn\Snowflake;

use ZMQContext, ZMQSocket, ZMQ;

/**
 * Snowflake ZeroMQ server
 *
 * Creates a TCP server that accepts plain text commands.
 *
 * Receives a command "NEXT" to generate and return a new ID.
 *
 * @package LucasVscn\Snowflake
 */
class Server
{
    /** @var IdWorker */
    protected $worker;

    /** @var integer */
    protected $port;

    /**
     * Snowflake Server constructor
     *
     * @param integer $workerId
     * @param integer $datacenterId
     * @param integer $port
     */
    public function __construct($workerId, $datacenterId, $port = 5599)
    {
        $this->port   = $port;

        $this->worker = new IdWorker($workerId, $datacenterId);
    }

    /**
     * This method should be called in order to start the server.
     *
     * @return void
     */
    public function run()
    {
        $context  = new ZMQContext();
        $receiver = new ZMQSocket($context, ZMQ::SOCKET_REP);

        $receiver->bind('tcp://*:' . $this->port);

        while (TRUE) {
            $msg = $receiver->recv();
            switch ($msg) {
                case 'NEXT':
                        $response = $this->worker->nextId();
                    break;
                default:
                    $response = 'UNKNOWN COMMAND';
                    break;
            }
            $receiver->send($response);
        }
    }
}