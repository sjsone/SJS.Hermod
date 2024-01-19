<?php
declare(strict_types=1);

namespace SJS\Loki\Log\Backend;

use Neos\Flow\Log\Backend\AbstractBackend;
use SJS\Loki\Client\LokiClient;
use SJS\Loki\Client\LokiClientConfiguration;

class LokiBackend extends AbstractBackend
{
    protected LokiClient $client;
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

        if (isset($options['maxBufferSize'])) {
            $this->maxBufferSize = $options['maxBufferSize'];
        }

        if (isset($options['logIpAddress'])) {
            $this->logIpAddress = $options['logIpAddress'];
        } else {
            $this->logIpAddress = false;
        }

        if (isset($options['labels']) && !is_array($options['labels'])) {
            throw new \Exception("LokiBackend: if labels are set, they MUST be an array");
        }

        $lokiClientConfiguration = new LokiClientConfiguration(
            $options['user'],
            $options['token'],
            $options['url'],
            $options['labels'] ?? []
        );

        $this->client = new LokiClient($lokiClientConfiguration);
    }

    public function open(): void
    {
        // stub
    }

    public function append(string $message, int $severity = LOG_INFO, $additionalData = null, string $packageKey = null, string $className = null, string $methodName = null): void
    {
        $labels = [
            "severity" => $severity
        ];

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
        $severityLabel = $this->severityLabels[$severity] ?? 'UNKNOWN  ';

        $values = [
            [
                floor(microtime(true) * 1000) . "000000",
                $ipAddress . $severityLabel . " " . $message . ($additionalData ? json_encode($additionalData) : "")
            ]
        ];

        $stream = $this->client->buildStream($values, $labels);

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
        $streams = $this->streamBuffer;

        $this->streamBuffer = [];

        $this->client->send($streams);
    }
}
