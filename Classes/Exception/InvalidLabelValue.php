<?php
namespace SJS\Hermod\Exception;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

class InvalidLabelValue extends \Exception {
    public function __construct(string $where, string $label) {
        parent::__construct("$where: could not build stream due to value of label '$label'");
    }
}