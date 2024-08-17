<?php
namespace SJS\Hermod\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use SJS\Hermod\Exception;
use Psr\Log\LoggerInterface;
use Neos\Flow\Log\Utility\LogEnvironment;

class LokiTestCommandController extends CommandController
{
    #[Flow\Inject]
    protected LoggerInterface $systemLogger;

    public function exceptionCommand()
    {
        throw new Exception\Test('Testing the Loki client', 6942066669);
    }

    public function logCommand(string $message = "Test") {
        $this->systemLogger->notice($message, LogEnvironment::fromMethodName(__METHOD__));
    }
}
