<?php

namespace LogglyIntegrationTestCommandBundle\Command;

use LogglyIntegrationTestCommandBundle\HandlerNotDefinedException;
use Monolog\Handler\LogglyHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LogglyListHandlersCommand
 * @package LogglyTestBundle\Command
 */
class LogglyListHandlersCommand extends ContainerAwareCommand
{
    const TYPE_LOGGLY = 'loggly';
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('loggly:handlers')
            ->setDescription('Prints all defined loggly handlers.')
            ->setHelp(<<<EOF
The <info>%command.name%</info> prints all loggly handlers:

  <info>php %command.full_name%</info>
EOF
            )
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serviceIds = $this->getContainer()->getServiceIds();

        $output->writeln('Loggly handlers');
        foreach ($serviceIds as $serviceId) {
            $matched = [];
            if (preg_match('~monolog\.handler\.([a-zA-Z0-9_]+)~', $serviceId, $matched)) {
                if ($this->getContainer()->get(sprintf('monolog.handler.%s', $matched[1])) instanceof LogglyHandler) {
                    $output->writeln(sprintf('<info>%s</info>', $matched[1]));
                }
            }
        }
    }
}
