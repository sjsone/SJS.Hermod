<?php
namespace SJS\Hermod\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

class LokiCommandController extends CommandController
{

    public function testCommand()
    {
        throw new \Exception('Testing the Hermod client', 6942066669);
    }
}
