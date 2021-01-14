<?php

namespace CRON\FormBuilder\Eel\Helper;

use DateTime;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Security\Cryptography\HashService;

class TimeHashHelper implements ProtectedContextAwareInterface {

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * @return string a hashed timestamp to protect the form for spam bots
     */
    public function getTimeHash(): string
    {
        return $this->createTimeHash();
    }

    /**
     * Creates an hashed timestamp
     * @return string
     */
    protected function createTimeHash(): string
    {
        $time = new DateTime();
        return $this->hashService->appendHmac((string)$time->getTimestamp());
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
