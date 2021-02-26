<?php

namespace CRON\FormBuilder\Eel\Helper;

use CRON\FormBuilder\Service\HoneyPotService;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

class TimeHashHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var HoneyPotService
     */
    protected $honeyPotService;

    /**
     * @return string a hashed timestamp to protect the form for spam bots
     */
    public function getTimeHash(): string
    {
        return $this->honeyPotService->getSecret();
    }

    /**
     * All methods are considered safe, i.e. can be executed from within Eel
     *
     * @param string $methodName
     * @return boolean
     *
     * @noinspection PhpMissingParamTypeInspection,PhpMissingReturnTypeInspection
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
