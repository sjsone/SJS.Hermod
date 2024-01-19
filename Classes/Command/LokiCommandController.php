<?php
namespace SJS\Loki\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

class LokiCommandController extends CommandController
{

    public function testCommand()
    {
        throw new \Exception('Testing the Loki client', 6942066669);
    }
}