<?php

namespace CRON\FormBuilder\Service;

use DateTime;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Security\Cryptography\HashService;

class HoneyPotService
{
    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $systemLogger;

    /**
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $conf;

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * Returns an encrypted secret
     * @return string
     */
    public function getSecret(): string
    {
        return $this->createTimeHash();
    }

    /**
     * Creates an hashed timestamp
     * @return string
     */
    private function createTimeHash(): string
    {
        $time = new DateTime();
        return $this->hashService->appendHmac((string)$time->getTimestamp());
    }

    /**
     * Checks the passed secret
     * @param string $hashedTimeStamp
     * @return bool
     */
    public function validateSecret(string $hashedTimeStamp): bool
    {
        return $this->checkTimestamp($hashedTimeStamp);
    }

    /**
     * Validates if the Honeypot field has been changed and if the transmission time is correct
     * @param string $hashedTimeStamp
     * @return bool
     */
    private function checkTimestamp(string $hashedTimeStamp): bool
    {
        $minimalSubmitDelayInSeconds = $this->conf['Protection']['minimalSubmitDelayInSeconds'];
        $maximalSubmitDelayInSeconds = $this->conf['Protection']['maximalSubmitDelayInSeconds'];

        try {
            $this->hashService->validateAndStripHmac($hashedTimeStamp);
            $validatedTimeStamp = (int)$this->hashService->validateAndStripHmac($hashedTimeStamp);

            $currentTime = new DateTime();
            $currentTime = $currentTime->getTimestamp();
            if (
                ($currentTime - $validatedTimeStamp) <= $minimalSubmitDelayInSeconds ||
                ($currentTime - $validatedTimeStamp) >= $maximalSubmitDelayInSeconds
            ) {
                $this->systemLogger->log(
                    'Minimum oder Maximum time in seconds after delivery of the page was exceeded !',
                    LOG_ERR
                );
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->systemLogger->log(
                'The current time stamp was changed !',
                LOG_ERR,
                ['exception' => $e]
            );
            return true;
        }
    }
}
