<?php

namespace SJS\Loki\Client;

use GuzzleHttp;
use Neos\Flow\Annotations as Flow;


class LokiClient
{
    protected GuzzleHttp\Client $client;

    public function __construct(protected LokiClientConfiguration $configuration)
    {
        $this->client = new GuzzleHttp\Client([
            GuzzleHttp\RequestOptions::AUTH => [
                $this->configuration->user,
                $this->configuration->token
            ],
            GuzzleHttp\RequestOptions::TIMEOUT => 0.5
        ]);
    }

    public function buildStream(array $values, array $withLabels = null): array
    {
        $labels = $this->configuration->labels;

        if ($withLabels) {
            foreach ($withLabels as $label => $value) {
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
        $body = [
            "streams" => $streams
        ];

        try {
            $this->client->post($this->configuration->url, [
                GuzzleHttp\RequestOptions::JSON => $body
            ]);
        } catch (\Throwable $th) {
            // TODO: in case of error do not try any again and log streams to file instead
        }
    }
}
