<?php

namespace SJS\Hermod\Client;

use GuzzleHttp;
use Neos\Flow\Annotations as Flow;
use SJS\Hermod\Exception;
use SJS\Hermod\Client\LokiClient\Configuration;


class LokiClient
{
    protected GuzzleHttp\Client $client;

    protected mixed $fallbackFileHandler = null;
    protected string $senderName = "sendToLoki";

    public function __construct(protected Configuration $configuration)
    {
        $this->client = new GuzzleHttp\Client([
            GuzzleHttp\RequestOptions::AUTH => [
                $this->configuration->user,
                $this->configuration->token
            ],
            GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => $configuration->connectTimeout,
            GuzzleHttp\RequestOptions::READ_TIMEOUT => $configuration->readTimeout,
        ]);

        if($this->configuration->fallbackFile) {
            $this->fallbackFileHandler = \fopen($this->configuration->fallbackFile, "a");
        }
    }

    public function buildStream(array $values, array $withLabels = null): array
    {
        $labels = $this->configuration->labels;

        if ($withLabels) {
            foreach ($withLabels as $label => $value) {
                if (!is_string($value) && !is_numeric($value)) {
                    throw new Exception\InvalidLabelValue("LokiClient", $label);
                }
                $labels[$label] = $value;
            }
        }

        return [
            "stream" => $labels,
            "values" => $values
        ];
    }

    public function send(array $streams)
    {
        $senderName = $this->senderName;
        $this->$senderName($streams);
    }

    protected function sendToLoki(array $streams)
    {
        try {
            $this->client->post($this->configuration->url, [
                GuzzleHttp\RequestOptions::JSON => [
                    "streams" => $streams
                ]
            ]);
        } catch (\Throwable $th) {
            // TODO: more configurable and extensible sender resolving and error handling
            if ($this->configuration->fallbackFile && $this->fallbackFileHandler = \fopen($this->configuration->fallbackFile, "a")) {
                $this->senderName = "sendToFallbackFile";
                $this->sendToFallbackFile($streams);
            } else {
                $this->senderName = "sendToNull";
            }
        }
    }

    protected function sendToFallbackFile(array $streams)
    {
        foreach ($streams as $stream) {
            fwrite($this->fallbackFileHandler, json_encode($stream) . "\n");
        }
    }

    protected function sendToNull()
    {
        // do nothing
    }
}
