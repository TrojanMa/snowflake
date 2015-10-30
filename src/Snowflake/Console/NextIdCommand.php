<?php

namespace LucasVscn\Snowflake\Console;

use ZMQContext, ZMQSocket, ZMQ;
use LucasVscn\Snowflake\IdWorker;
use LucasVscn\Snowflake\Client as SnowflakeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NextIdCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('next')
            ->setDescription('Asks the server for the next Id')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Set the connection port.', 5599);
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
        $id = (new SnowflakeClient(
                'localhost',
                $input->getOption('port'))
            )->nextId();

        $output->writeln($id);
    }
}