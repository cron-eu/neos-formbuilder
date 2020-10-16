<?php

namespace CRON\FormBuilder\Eel\Helper;

use DateTime;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

class TimeHashHelper implements ProtectedContextAwareInterface {

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Log\SystemLoggerInterface
     *
     */

    protected $systemLogger;

    /**
     * @return string a encrypted timestamp to protect the form for spam bots
     */
    public function getTimeHash()
    {
        return $this->createTimeHash();
    }

    /**
     * Creates an encrypted timestamp
     * @return string
     */
    protected function createTimeHash()
    {

        $key = 'honypot876574';
        $iv = '0259847523614897';
        $cipher = 'aes-128-cbc';

        $time = new DateTime();
        try {
            return openssl_encrypt($time->getTimestamp(), $cipher, $key, 0, $iv);
        } catch (\Exception $e) {
            $this->systemLogger->logThrowable($e);
        }
    }

    /**
     * All methods are considered safe, i.e. can be executed from within Eel
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
