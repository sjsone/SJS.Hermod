<?php
declare(strict_types=1);

namespace SJS\Loki\Log\Backend;

use Neos\Flow\Log\Backend\AbstractBackend;
use GuzzleHttp;

class LokiBackend extends AbstractBackend
{
    protected GuzzleHttp\Client $client;
    protected string $url;
    protected string $user;
    protected string $token;
    protected array $streamBuffer = [];
    protected ?array $labels;

    protected int $maxBufferSize = 150;

    protected array $severityLabels = [
        LOG_EMERG => 'EMERGENCY',
        LOG_ALERT => 'ALERT    ',
        LOG_CRIT => 'CRITICAL ',
        LOG_ERR => 'ERROR    ',
        LOG_WARNING => 'WARNING  ',
        LOG_NOTICE => 'NOTICE   ',
        LOG_INFO => 'INFO     ',
        LOG_DEBUG => 'DEBUG    ',
    ];

    /**
     * Constructs this log backend
     *
     * @param mixed $options Configuration options
     * @api
     */
    public function __construct(array $options = [])
    {
        $this->severityThreshold = $options['severityThreshold'];
        $this->url = $options['url'];
        $this->user = $options['user'];
        $this->token = $options['token'];

        if (isset($options['maxBufferSize'])) {
            $this->maxBufferSize = $options['maxBufferSize'];
        }

        if (isset($options['logIpAddress'])) {
            $this->logIpAddress = $options['logIpAddress'];
        } else {
            $this->logIpAddress = false;
        }

        if (isset($options['labels'])) {
            if (!is_array($options['labels'])) {
                throw new \Exception("LokiBackend: if labels are set, they MUST be an array");
            }
            if (!empty($options['labels']) && array_is_list($options['labels'])) {
                throw new \Exception("LokiBackend: labels MUST be an associative array");
            }
            $this->labels = $options['labels'];
        }
    }

    public function open(): void
    {
        $this->client = new GuzzleHttp\Client([
            GuzzleHttp\RequestOptions::AUTH => [
                $this->user,
                $this->token
            ],
            GuzzleHttp\RequestOptions::TIMEOUT => 0.5
        ]);
    }

    public function append(string $message, int $severity = LOG_INFO, $additionalData = null, string $packageKey = null, string $className = null, string $methodName = null): void
    {
        $severityLabel = $this->severityLabels[$severity] ?? 'UNKNOWN  ';

        $labels = $this->labels ?? [];
        $labels["severity"] = $severity;

        if ($packageKey) {
            $labels['packageKey'] = $packageKey;
        }
        if ($className) {
            $labels['className'] = $className;
        }
        if ($methodName) {
            $labels['methodName'] = $methodName;
        }

        $ipAddress = ($this->logIpAddress === true) ? str_pad(($_SERVER['REMOTE_ADDR'] ?? ''), 15) . ' ' : '';

        $stream = [
            "stream" => $labels,
            "values" => [
                [
                    floor(microtime(true) * 1000) . "000000",
                    $ipAddress . $severityLabel . " " . $message . ($additionalData ? json_encode($additionalData) : "")
                ]
            ]
        ];

        $this->streamBuffer[] = $stream;

        if (count($this->streamBuffer) >= $this->maxBufferSize) {
            $this->flushAndSendStreamBuffer();
        }
    }

    public function close(): void
    {
        if (count($this->streamBuffer) > 0) {
            $this->flushAndSendStreamBuffer();
        }
    }

    protected function flushAndSendStreamBuffer()
    {
        $body = [
            "streams" => $this->streamBuffer
        ];

        $this->streamBuffer = [];

        try {
            $this->client->post($this->url, [
                GuzzleHttp\RequestOptions::JSON => $body
            ]);
        } catch (\Throwable $th) {
            // TODO: in case of error do not try any again and log streams to file instead
        }
    }
}
