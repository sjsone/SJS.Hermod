<?php
namespace SJS\Hermod\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use SJS\Hermod\Exception;

class LokiCommandController extends CommandController
{

    public function testCommand()
    {
        throw new Exception\Test('Testing the Loki client', 6942066669);
    }
}
