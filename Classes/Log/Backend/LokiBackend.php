<?php
declare(strict_types=1);

namespace SJS\Hermod\Log\Backend;

use Neos\Flow\Log\Backend\AbstractBackend;
use SJS\Hermod\Client\LokiClient;
use SJS\Hermod\Client\LokiClient\Configuration;
use SJS\Hermod\Exception;

class LokiBackend extends AbstractBackend
{
    protected LokiClient $client;
    protected string $url;
    protected string $user;
    protected string $token;
    protected array $streamBuffer = [];
    protected ?array $labels;

    protected int $maxBufferSize = 150;

    protected int $pseudoNanoSecondCounter = 0;
    protected int $pseudoNanoSecondPadLength = 6;
    protected int $pseudoNanoSecondMaxSize;

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

    protected bool $closed = false;

    /**
     * Constructs this log backend
     *
     * @param mixed $options Configuration options
     * @api
     */
    public function __construct(array $options = [])
    {
        $this->pseudoNanoSecondMaxSize = (int) pow(10, $this->pseudoNanoSecondPadLength);

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
            throw new Exception\WronglyConfiguredLabels("LokiBackend");
        }

        $lokiClientConfiguration = new Configuration(
            $options['user'],
            $options['token'],
            $options['url'],
            $options['connectTimeout'] ?? 0.1,
            $options['readTimeout'] ?? 0.1,
            $options['labels'] ?? [],
            $options['fallbackFile'] ?? null
        );

        $this->client = new LokiClient($lokiClientConfiguration);
    }

    public function open(): void
    {
        // stub
    }

    protected function getNextPseudoNanoSeconds(): string
    {
        $pseudoNanoSeconds = str_pad((string) $this->pseudoNanoSecondCounter, $this->pseudoNanoSecondPadLength, "0", STR_PAD_LEFT);
        
        $this->pseudoNanoSecondCounter++;
        if ($this->pseudoNanoSecondCounter >= $this->pseudoNanoSecondMaxSize) {
            $this->pseudoNanoSecondCounter = 0;
        }

        return $pseudoNanoSeconds;
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

        if (isset($additionalData["labels"]) && is_iterable($additionalData["labels"])) {
            foreach ($additionalData["labels"] as $key => $value) {
                if(!$key || !is_string($key)) {
                    continue;
                }

                $labels[$key] = (string) $value;
            }
        }

        $ipAddress = ($this->logIpAddress === true) ? str_pad(($_SERVER['REMOTE_ADDR'] ?? ''), 15) . ' ' : '';
        $severityLabel = $this->severityLabels[$severity] ?? 'UNKNOWN  ';

        $values = [
            [
                floor(microtime(true) * 1000) . $this->getNextPseudoNanoSeconds(),
                $ipAddress . $severityLabel . " " . $message . ($additionalData ? json_encode($additionalData) : "")
            ]
        ];

        $stream = $this->client->buildStream($values, $labels);

        $this->streamBuffer[] = $stream;

        if (count($this->streamBuffer) >= $this->maxBufferSize) {
            $this->flushAndSendStreamBuffer();
        }
    }

    protected function flushAndSendStreamBuffer()
    {

        $streams = $this->streamBuffer;

        $this->streamBuffer = [];

        $this->client->send($streams);

        $this->pseudoNanoSecondCounter = 0;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        if (count($this->streamBuffer) > 0) {
            $this->flushAndSendStreamBuffer();
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}