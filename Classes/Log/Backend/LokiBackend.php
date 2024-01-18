<?php
declare(strict_types=1);

namespace SJS\Loki\Log\Backend;

use Neos\Flow\Log\Backend\AbstractBackend;
use GuzzleHttp;

class LokiBackend extends AbstractBackend
{
    protected GuzzleHttp\Client $client;

    protected string $loggerName;
    protected string $url;
    protected string $user;
    protected string $token;
    protected array $streamBuffer = [];

    protected int $flushStreamBufferOnSize = 150;

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
        $this->loggerName = $options['loggerName'];
        $this->url = $options['url'];
        $this->user = $options['user'];
        $this->token = $options['token'];

        if (isset($options['logIpAddress'])) {
            $this->logIpAddress = $options['logIpAddress'];
        } else {
            $this->logIpAddress = false;
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

        $labels = [
            "target" => $this->loggerName,
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

        if (count($this->streamBuffer) > $this->flushStreamBufferOnSize) {
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
