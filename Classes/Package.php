<?php
namespace SJS\Hermod;

use Neos\Flow\Core\Booting\Sequence;
use Neos\Flow\Core\Booting\Step;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use SJS\Hermod\Service\LokiExceptionService;

class Package extends BasePackage
{

    /**
     * {@inheritdoc}
     */
    public function boot(Bootstrap $bootstrap)
    {
        $bootstrap->getSignalSlotDispatcher()->connect(
            Sequence::class,
            'afterInvokeStep',
            function (Step $step, $runlevel) use ($bootstrap) {
                if ($step->getIdentifier() === 'neos.flow:objectmanagement:runtime') {
                    // This triggers the initializeObject method
                    $bootstrap->getObjectManager()->get(LokiExceptionService::class);
                }
            }
        );
    }
}
