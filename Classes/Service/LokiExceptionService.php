<?php

namespace SJS\Hermod\Service;

use Neos\Flow\Annotations as Flow;
use SJS\Hermod\Client\LokiClient;
use SJS\Hermod\Client\LokiClient\Configuration;


#[Flow\Scope("singleton")]
class LokiExceptionService
{
    protected LokiClient $lokiClient;

    #[Flow\InjectConfiguration("exceptionService")]
    protected array $configuration;

    public function initializeObject()
    {
        $lokiClientConfiguration = new Configuration(
            $this->configuration['user'],
            $this->configuration['token'],
            $this->configuration['url'],
            $this->configuration['connectTimeout'],
            $this->configuration['readTimeout'],
            $this->configuration['labels'],
            $this->configuration['fallbackFile'],
        );

        $this->lokiClient = new LokiClient($lokiClientConfiguration);
    }


    public function handleThrowable(\Throwable $throwable, bool $attachFile = true, bool $attachTrace = false)
    {
        $labels = [
            "code" => (string) $throwable->getCode(),
        ];

        if ($throwable instanceof \Neos\Flow\Exception) {
            $labels['referenceCode'] = (string) $throwable->getReferenceCode();
            $labels['statusCode'] = (string) $throwable->getStatusCode();
        }

        $message = $throwable::class . ": " . $throwable->getMessage();
        if ($attachFile) {
            $file = $throwable->getFile();
            $line = $throwable->getLine();
            $message .= " \nFile: $file:$line";
        }
        if ($attachTrace) {
            $message .= " \nTrace: " . $throwable->getTraceAsString();
        }

        $values = [
            [
                floor(microtime(true) * 1000) . "000000",
                $message
            ]
        ];

        $stream = $this->lokiClient->buildStream($values, $labels);
        $this->lokiClient->send([$stream], true);
    }
}
