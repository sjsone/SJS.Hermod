<?php
namespace SJS\Hermod\Exception;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

class MissingAllAuthenticationOptions extends \Exception {
    public function __construct(string $where) {
        parent::__construct("$where: 'user', 'token' and 'url' are not set. \nIf you just installed the package make shure that the environment variable are set as described in the Readme.");
    }
}