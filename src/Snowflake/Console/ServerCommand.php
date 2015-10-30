<?php

namespace LucasVscn\Snowflake\Console;

use ZMQContext, ZMQSocket, ZMQ;
use LucasVscn\Snowflake\IdWorker;
use LucasVscn\Snowflake\Server as SnowflakeServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('server')
            ->setDescription('Start the Snowflake ZeroMQ server')
            ->addOption('worker', null, InputOption::VALUE_OPTIONAL, 'Worker Id.', 1)
            ->addOption('dc', null, InputOption::VALUE_OPTIONAL, 'Datacenter Id.', 1)
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Set the server port.', 5599);
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        (new SnowflakeServer(
            $input->getOption('worker'),
            $input->getOption('dc'),
            $input->getOption('port')
        ))->run();
    }
}
