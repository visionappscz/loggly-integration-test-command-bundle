<?php

namespace LogglyIntegrationTestCommandBundle\Command;

use LogglyIntegrationTestCommandBundle\Exception\HandlerNotDefinedException;
use LogglyIntegrationTestCommandBundle\Exception\InvalidSeverityException;
use Monolog\Handler\LogglyHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LogglyTestCommand
 * @package LogglyTestBundle\Command
 */
class LogglyTestCommand extends ContainerAwareCommand
{
    const TEST_MESSAGE = 'test log message';
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('loggly:test')
            ->setAliases(array(
                'test:loggly',
            ))
            ->setDefinition([
                new InputArgument('handler', InputArgument::OPTIONAL, 'Name of the handler to be tested', 'all'),
                new InputArgument('severity', InputArgument::OPTIONAL, 'Severity of the test event', 'all'),
            ])
            ->setDescription('Tests loggly integration by sending a log message.')
            ->setHelp(<<<EOF
The <info>%command.name%</info> tests loggly integration:

  <info>php %command.full_name% </info>
  <info>php %command.full_name% [handler|--all [severity|--all]]</info>
EOF
            )
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->emitEvents($output, $input->getArgument('handler'), $input->getArgument('severity'));
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $handler
     * @param $severity
     * @throws \LogglyIntegrationTestCommandBundle\Exception\HandlerNotDefinedException
     * @throws \LogglyIntegrationTestCommandBundle\Exception\InvalidSeverityException
     */
    protected function emitEvents(OutputInterface $output, $handler, $severity)
    {
        if ($handler === 'all') {
            $output->writeln(sprintf('Test all defined loggly loggers...'));
            $handlers = $this->getLogglyHandlers();
            if (empty($handlers)) {
                throw new HandlerNotDefinedException('No loggly handler defined');
            }
        } else {
            $output->writeln(sprintf('Test loggly logger <info>%s</info>...', $handler));
            $handlers = [$this->getLogglyHandler($handler)];
            if (empty($handlers)) {
                throw new HandlerNotDefinedException(sprintf('Loggly handler %s is not defined', $handler));
            }
        }
        $logger = $this->createTestLogger($handlers);

        if ($severity === 'all') {
            $testLevels = Logger::getLevels();
        } else {
            $levels = Logger::getLevels();
            $level = array_key_exists($severity, $levels) ? $levels[$severity] : null;
            if ($level === null) {
                throw new InvalidSeverityException(sprintf('Severity %s is not valid', $severity));
            }
            $testLevels = [$severity => $level];
        }

        $maxLevelName = strlen('severity');
        foreach ($testLevels as $levelName => $level) {
            $maxLevelName = max($maxLevelName, mb_strlen($levelName));
        }
        $format  = 'Severity <comment>%-'.($maxLevelName + 1).'s</comment>...';
        foreach ($testLevels as $levelName => $level) {
            $output->write(sprintf($format, $levelName));
            if ($logger->addRecord($level, self::TEST_MESSAGE)) {
                $output->writeln(sprintf(' <info>SENT</info>'));
            } else {
                $output->writeln(sprintf(' <error>NOT SENT</error>'));
            }
        }
        $output->writeln(sprintf('<info>DONE.</info>'));
    }

    /**
     * @throws \InvalidArgumentException When route does not exist
     */
    private function getLogglyHandlers()
    {
        /** @var Logger $logger */
        $logger = $this->getContainer()->get('logger');
        
        $handlers = [];
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof LogglyHandler) {
                $handlers[] = $handler;
            }
        }

        return $handlers;
    }

    /**
     * @param $name
     * @return LogglyHandler
     * @throws \LogglyIntegrationTestCommandBundle\Exception\HandlerNotDefinedException
     */
    private function getLogglyHandler($name)
    {
        $serviceId = sprintf('monolog.handler.%s', $name);
        if (!$this->getContainer()->has($serviceId)) {
            throw new HandlerNotDefinedException(sprintf('Handler %s is not defined', $name));
        }

        $logglyHandler = $this->getContainer()->get($serviceId);
        if (!($logglyHandler instanceof LogglyHandler)) {
            throw new HandlerNotDefinedException(sprintf('Handler %s is not loggly compatible', $name));
        }
        
        return $logglyHandler;
    }

    /**
     * @param array $handlers
     * @return \Monolog\Logger
     */
    private function createTestLogger(array $handlers)
    {
        return new Logger('test', $handlers);
    }
}
