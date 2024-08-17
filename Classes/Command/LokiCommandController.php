<?php
namespace SJS\Hermod\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use SJS\Hermod\Exception;
use SJS\Hermod\Client\LokiClient;
use SJS\Hermod\Client\LokiClient\Configuration;
use Psr\Log\LoggerInterface;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Log\Utility\LogEnvironment;

class LokiCommandController extends CommandController
{
    #[Flow\Inject]
    protected LoggerInterface $systemLogger;
    
    #[Flow\Inject]
    protected ConfigurationManager $configurationManager;

    protected function createClientWithoutFallbackFile(array $configuration): LokiClient
    {
        $lokiClientConfiguration = new Configuration(
            $configuration['user'],
            $configuration['token'],
            $configuration['url'],
            $configuration['connectTimeout'],
            $configuration['readTimeout'],
            $configuration['labels'],
        );

        return new LokiClient($lokiClientConfiguration);
    }

    /**
     * Sends the streams in a fallback file back to Loki
     *
     * Each stream in the file gets read, send to Loki and deleted.
     * The --configuration-path is the path to the client configuration. For Example:
     *     --configuration-path "Neos.Flow.log.psr3.'Neos\Flow\Log\PsrLoggerFactory'.systemLogger.default.options"
     *     --configuration-path "SJS.Hermod.exceptionService"
     *
     * @param string $configurationPath to the client configuration
     */
    public function sendFallbackCommand(string $configurationPath = "")
    {
        $configuration = $this->configurationManager->getConfiguration("Settings", $configurationPath);
        if(!$configuration || !is_array($configuration)) {
            $this->output("<error>Could not read configuration</error>\n");
            return;
        }

        $client = $this->createClientWithoutFallbackFile($configuration);

        $fallbackFile = $configuration["fallbackFile"] ?? null;
        if(!is_string($fallbackFile)) {
            $this->output("<error>Incompatible type: </error> $fallbackFile\n");
            return;
        }

        $fallbackFileHandler = \fopen($fallbackFile, "r+");
        if(!$fallbackFileHandler) {
            $this->output("<error>Could not open file handler for: </error> $fallbackFile\n");
            return;
        }

        while (($line = fgets($fallbackFileHandler)) !== false) {
            try {
                $stream = json_decode($line);
                $client->send([$stream]);
            }catch(\Throwable $th) {
                // TODO: log error in json encoding
            }

            $pos = ftell($fallbackFileHandler);
            $remaining = stream_get_contents($fallbackFileHandler);
            fseek($fallbackFileHandler, 0);    
            fwrite($fallbackFileHandler, $remaining);
    
            ftruncate($fallbackFileHandler, ftell($fallbackFileHandler));
    
            fseek($fallbackFileHandler, $pos - strlen($line));
        }
    
        fclose($fallbackFileHandler);
    }
}
