<?php

namespace SJS\Hermod\Handler;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\DebugExceptionHandler as FlowDebugExceptionHandler;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use SJS\Hermod\Service\LokiExceptionService;
use Neos\Flow\Core\Bootstrap;


class DebugExceptionHandler extends FlowDebugExceptionHandler
{
    /**
     * @param \Throwable $exception
     * {@inheritdoc}
     */
    public function echoExceptionWeb($exception)
    {
        if (!($exception instanceof \Throwable)) {
            $this->sendExceptionToLoki($exception);
        }
        parent::echoExceptionWeb($exception);
    }

    /**
     * {@inheritdoc}
     */
    public function echoExceptionCLI(\Throwable $exception, bool $exceptionWasLogged)
    {
        $this->sendExceptionToLoki($exception);
        parent::echoExceptionCLI($exception, $exceptionWasLogged);
    }

    protected function sendExceptionToLoki(\Throwable $exception)
    {
        if (!(Bootstrap::$staticObjectManager instanceof ObjectManagerInterface)) {
            return;
        }

        $options = $this->resolveCustomRenderingOptions($exception);
        $lokiIgnoreException = $options['lokiIgnoreException'] ?? false;
        if ($lokiIgnoreException) {
            return;
        }

        try {
            Bootstrap::$staticObjectManager->get(LokiExceptionService::class)?->handleThrowable($exception);
        } catch (\Exception $exception) {
            // Let's not throw any exceptions...
        }
    }
}
