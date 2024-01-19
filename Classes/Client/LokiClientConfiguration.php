<?php

namespace SJS\Loki\Client;

use GuzzleHttp;
use Neos\Flow\Annotations as Flow;


class LokiClientConfiguration
{
    public function __construct(
        public readonly string $user,
        public readonly string $token,
        public readonly string $url,
        public readonly array $labels = []
        // TODO: backup log file path
    ) {
        if (!empty($this->labels) && array_is_list($this->labels)) {
            throw new \Exception("LokiClientConfiguration: labels MUST be an associative array");
        }
    }
}
