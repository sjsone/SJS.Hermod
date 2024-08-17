<?php
namespace SJS\Hermod\Exception;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

class WronglyConfiguredLabels extends \Exception {
    public function __construct(string $where) {
        parent::__construct("$where: if labels are set, they MUST be an associative array");
    }
}
