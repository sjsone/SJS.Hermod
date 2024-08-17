<?php

namespace SJS\Hermod\Client\LokiClient;

use GuzzleHttp;
use Neos\Flow\Annotations as Flow;
use SJS\Hermod\Exception;


class Configuration
{
    public function __construct(
        public readonly string $user,
        public readonly string $token,
        public readonly string $url,
        public readonly float $connectTimeout = 0.1,
        public readonly float $readTimeout = 0.1,
        public readonly array $labels = [],
        public readonly ?string $fallbackFile = null
    ) {
        if (!empty($this->labels) && array_is_list($this->labels)) {
            throw new Exception\WronglyConfiguredLabels("LokiClientConfiguration");
        }
    }
}
